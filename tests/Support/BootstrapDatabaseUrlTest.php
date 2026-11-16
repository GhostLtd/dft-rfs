<?php

namespace App\Tests\Support;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * tests/bootstrap.php resets the database DATABASE_URL names. It checks one value, so it must refuse to run if the
 * places that value can come from disagree, rather than check one database and reset another (its console commands
 * read $_ENV before $_SERVER). Both URLs here are scratch SQLite files, so a regression can't harm anything real.
 */
class BootstrapDatabaseUrlTest extends TestCase
{
    private string $directory;

    #[\Override]
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/bootstrap-url-test-' . uniqid();
        (new Filesystem())->mkdir($this->directory);
    }

    #[\Override]
    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testRefusesConflictingDatabaseUrlsBeforeResettingAnything(): void
    {
        $checked = "sqlite:///{$this->directory}/checked.sqlite";
        $unchecked = "sqlite:///{$this->directory}/unchecked.sqlite";
        file_put_contents("{$this->directory}/unchecked.sqlite", 'must survive');

        // As a phpunit.xml <server> value would, set $_SERVER to one URL while $_ENV holds another
        $script = sprintf(
            '$_SERVER["DATABASE_URL"] = %s; $_ENV["DATABASE_URL"] = %s; require %s;',
            var_export($checked, true),
            var_export($unchecked, true),
            var_export(dirname(__DIR__) . '/bootstrap.php', true),
        );
        // Without SYMFONY_DOTENV_VARS (inherited from this run's own bootstrap), Dotenv in the child would treat
        // DATABASE_URL as its own and replace both values with .env.test's, i.e. the real test database
        $process = new Process([PHP_BINARY, '-r', $script], dirname(__DIR__, 2), [
            'APP_ENV' => 'test',
            'DATABASE_URL' => false,
            'SYMFONY_DOTENV_VARS' => false,
        ]);
        $process->setTimeout(120);
        $process->run();

        $this->assertFalse($process->isSuccessful(), $process->getOutput());
        $this->assertStringContainsString('DATABASE_URL differs', $process->getErrorOutput() . $process->getOutput());
        $this->assertSame('must survive', file_get_contents("{$this->directory}/unchecked.sqlite"));
    }
}
