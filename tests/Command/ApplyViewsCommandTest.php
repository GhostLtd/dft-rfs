<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ApplyViewsCommand;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ApplyViewsCommandTest extends TestCase
{
    private string $projectDir;
    private Filesystem $fs;

    protected function setUp(): void
    {
        $this->fs = new Filesystem();
        $this->projectDir = sys_get_temp_dir() . '/apply-views-test-' . uniqid();
        $this->fs->mkdir([$this->projectDir . '/sql/views', $this->projectDir . '/sql/functions']);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            $this->fs->remove($this->projectDir);
        }
    }

    private function writeFile(string $relative, string $contents): void
    {
        file_put_contents($this->projectDir . '/' . $relative, $contents);
    }

    private function makeTester(bool $mysqlPlatform = true): CommandTester
    {
        $platform = $mysqlPlatform
            ? $this->createStub(AbstractMySQLPlatform::class)
            : $this->createStub(SQLitePlatform::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        return new CommandTester(new ApplyViewsCommand($em, $this->projectDir));
    }

    // ---------------------------------------------------------------------
    // Topological ordering
    // ---------------------------------------------------------------------

    public function testAppliesViewsInDependencyOrder(): void
    {
        $this->writeFile('sql/views/a.sql', "CREATE OR REPLACE VIEW a AS SELECT 1;");
        $this->writeFile('sql/views/b.sql', "-- @depends a\nCREATE OR REPLACE VIEW b AS SELECT * FROM a;");
        $this->writeFile('sql/views/c.sql', "-- @depends b\nCREATE OR REPLACE VIEW c AS SELECT * FROM b;");

        $tester = $this->makeTester();
        $exit = $tester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exit);
        $output = $tester->getDisplay();

        $posA = strpos($output, 'applying view a');
        $posB = strpos($output, 'applying view b');
        $posC = strpos($output, 'applying view c');
        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        $this->assertNotFalse($posC);
        $this->assertLessThan($posB, $posA, 'a must precede b');
        $this->assertLessThan($posC, $posB, 'b must precede c');
    }

    public function testFunctionsAndViewsShareTheOrdering(): void
    {
        $this->writeFile('sql/functions/my_fn.sql', "CREATE FUNCTION my_fn() RETURNS INT DETERMINISTIC RETURN 1;");
        $this->writeFile('sql/views/v.sql', "-- @depends my_fn\nCREATE OR REPLACE VIEW v AS SELECT my_fn() AS x;");

        $tester = $this->makeTester();
        $tester->execute(['--dry-run' => true]);

        $output = $tester->getDisplay();
        $posFn = strpos($output, 'applying function my_fn');
        $posV = strpos($output, 'applying view v');
        $this->assertLessThan($posV, $posFn, 'function must apply before dependent view');
    }

    public function testReportsDependencyCycle(): void
    {
        $this->writeFile('sql/views/a.sql', "-- @depends b\nCREATE OR REPLACE VIEW a AS SELECT 1;");
        $this->writeFile('sql/views/b.sql', "-- @depends a\nCREATE OR REPLACE VIEW b AS SELECT 1;");

        $tester = $this->makeTester();
        $exit = $tester->execute(['--dry-run' => true]);

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('Dependency cycle', $tester->getDisplay());
    }

    public function testReportsUnknownDependency(): void
    {
        $this->writeFile('sql/views/a.sql', "-- @depends nonexistent\nCREATE OR REPLACE VIEW a AS SELECT 1;");

        $tester = $this->makeTester();
        $exit = $tester->execute(['--dry-run' => true]);

        $this->assertSame(Command::FAILURE, $exit);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Unknown dependency', $output);
        $this->assertStringContainsString('nonexistent', $output);
    }

    public function testDetectsDuplicateNameAcrossSubdirectories(): void
    {
        $this->writeFile('sql/views/dup.sql', "CREATE OR REPLACE VIEW dup AS SELECT 1;");
        $this->writeFile('sql/functions/dup.sql', "CREATE FUNCTION dup() RETURNS INT DETERMINISTIC RETURN 1;");

        $tester = $this->makeTester();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate object name "dup"');
        $tester->execute(['--dry-run' => true]);
    }

    // ---------------------------------------------------------------------
    // --only / --no-deps
    // ---------------------------------------------------------------------

    public function testOnlyAppliesNamedObjectAndItsTransitiveDeps(): void
    {
        $this->writeFile('sql/views/a.sql', "CREATE OR REPLACE VIEW a AS SELECT 1;");
        $this->writeFile('sql/views/b.sql', "-- @depends a\nCREATE OR REPLACE VIEW b AS SELECT * FROM a;");
        $this->writeFile('sql/views/c.sql', "-- @depends b\nCREATE OR REPLACE VIEW c AS SELECT * FROM b;");
        $this->writeFile('sql/views/unrelated.sql', "CREATE OR REPLACE VIEW unrelated AS SELECT 1;");

        $tester = $this->makeTester();
        $tester->execute(['--only' => 'c', '--dry-run' => true]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('applying view a', $output);
        $this->assertStringContainsString('applying view b', $output);
        $this->assertStringContainsString('applying view c', $output);
        $this->assertStringNotContainsString('applying view unrelated', $output);
        $this->assertStringContainsString('3 object(s)', $output);
    }

    public function testOnlyWithNoDepsAppliesJustTheNamedObject(): void
    {
        $this->writeFile('sql/views/a.sql', "CREATE OR REPLACE VIEW a AS SELECT 1;");
        $this->writeFile('sql/views/b.sql', "-- @depends a\nCREATE OR REPLACE VIEW b AS SELECT * FROM a;");

        $tester = $this->makeTester();
        $tester->execute(['--only' => 'b', '--no-deps' => true, '--dry-run' => true]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('applying view b', $output);
        $this->assertStringNotContainsString('applying view a', $output);
        $this->assertStringContainsString('1 object(s)', $output);
    }

    public function testOnlyWithUnknownObjectNameFails(): void
    {
        $this->writeFile('sql/views/a.sql', "CREATE OR REPLACE VIEW a AS SELECT 1;");

        $tester = $this->makeTester();
        $exit = $tester->execute(['--only' => 'does_not_exist', '--dry-run' => true]);

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('No object named "does_not_exist"', $tester->getDisplay());
    }

    // ---------------------------------------------------------------------
    // parseDepends edge cases
    // ---------------------------------------------------------------------

    public function testDependsHeaderStopsAtFirstNonCommentLine(): void
    {
        // `-- @depends b` after the CREATE line must NOT be parsed as a dep
        $this->writeFile('sql/views/a.sql', "CREATE OR REPLACE VIEW a AS SELECT 1;\n-- @depends b");
        $this->writeFile('sql/views/b.sql', "CREATE OR REPLACE VIEW b AS SELECT 1;");

        $tester = $this->makeTester();
        $tester->execute(['--only' => 'a', '--dry-run' => true]);

        // If the stray @depends had been picked up, b would also be applied
        $this->assertStringNotContainsString('applying view b', $tester->getDisplay());
    }

    public function testDependsHeaderToleratesBlankAndNonDirectiveComments(): void
    {
        $this->writeFile('sql/views/a.sql', "CREATE OR REPLACE VIEW a AS SELECT 1;");
        $this->writeFile('sql/views/b.sql', "-- this is a plain comment\n\n-- @depends a\n-- another comment\nCREATE OR REPLACE VIEW b AS SELECT * FROM a;");

        $tester = $this->makeTester();
        $tester->execute(['--only' => 'b', '--dry-run' => true]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('applying view a', $output);
        $this->assertStringContainsString('applying view b', $output);
    }

    public function testMultipleDependsLines(): void
    {
        $this->writeFile('sql/views/a.sql', "CREATE OR REPLACE VIEW a AS SELECT 1;");
        $this->writeFile('sql/views/b.sql', "CREATE OR REPLACE VIEW b AS SELECT 1;");
        $this->writeFile('sql/views/c.sql', "-- @depends a\n-- @depends b\nCREATE OR REPLACE VIEW c AS SELECT * FROM a, b;");

        $tester = $this->makeTester();
        $tester->execute(['--only' => 'c', '--dry-run' => true]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('applying view a', $output);
        $this->assertStringContainsString('applying view b', $output);
        $this->assertStringContainsString('applying view c', $output);
    }

    // ---------------------------------------------------------------------
    // Platform gating and empty directory
    // ---------------------------------------------------------------------

    public function testSkipsOnNonMySQLPlatform(): void
    {
        $this->writeFile('sql/views/a.sql', "CREATE OR REPLACE VIEW a AS SELECT 1;");

        $tester = $this->makeTester(mysqlPlatform: false);
        $exit = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exit);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Skipping', $output);
        $this->assertStringNotContainsString('applying view', $output);
    }

    public function testEmptySqlDirectoryEmitsWarningAndSucceeds(): void
    {
        $tester = $this->makeTester();
        $exit = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertStringContainsString('No SQL files found', $tester->getDisplay());
    }

    // ---------------------------------------------------------------------
    // SQL execution: confirms view + function sequencing through Connection
    // ---------------------------------------------------------------------

    public function testExecutesViewAndFunctionStatementsAgainstConnection(): void
    {
        $this->writeFile('sql/functions/my_fn.sql', "CREATE FUNCTION my_fn() RETURNS INT DETERMINISTIC RETURN 1");
        $this->writeFile('sql/views/v.sql', "-- @depends my_fn\nCREATE OR REPLACE VIEW v AS SELECT my_fn() AS x");

        $platform = $this->createStub(AbstractMySQLPlatform::class);
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $calls = [];
        $connection
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$calls): int {
                $calls[] = $sql;
                return 0;
            });

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $tester = new CommandTester(new ApplyViewsCommand($em, $this->projectDir));
        $exit = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exit);

        // Expected sequence: DROP FUNCTION IF EXISTS my_fn, CREATE FUNCTION ..., CREATE OR REPLACE VIEW v ...
        $this->assertCount(3, $calls);
        $this->assertSame('DROP FUNCTION IF EXISTS my_fn', $calls[0]);
        $this->assertStringContainsString('CREATE FUNCTION my_fn', $calls[1]);
        $this->assertStringContainsString('CREATE OR REPLACE VIEW v', $calls[2]);
    }

    public function testDryRunDoesNotHitTheConnection(): void
    {
        $this->writeFile('sql/views/a.sql', "CREATE OR REPLACE VIEW a AS SELECT 1;");

        $platform = $this->createStub(AbstractMySQLPlatform::class);
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->expects($this->never())->method('executeStatement');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $tester = new CommandTester(new ApplyViewsCommand($em, $this->projectDir));
        $exit = $tester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertStringContainsString('Dry run OK', $tester->getDisplay());
    }
}
