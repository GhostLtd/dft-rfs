<?php

namespace App\Tests\Support;

use App\Tests\DataFixtures\MaintenanceWarningFixtures;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Production runs MySQL, which enforces foreign keys. SQLite only does so when each connection
 * issues `PRAGMA foreign_keys = ON`, so without SqliteForeignKeysMiddleware the test suite would
 * silently accept deletes/inserts that fail in production (e.g. the HGV-zero FK 1451 crash).
 */
class SqliteForeignKeysTest extends KernelTestCase
{
    private Connection $connection;
    private AbstractDatabaseTool $databaseTool;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->connection = $container->get('doctrine.dbal.default_connection');
        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();

        if ($this->connection->getDatabasePlatform()::class !== \Doctrine\DBAL\Platforms\SQLitePlatform::class) {
            $this->markTestSkipped('SQLite-specific');
        }
    }

    public function testForeignKeysAreEnforcedAfterBoot(): void
    {
        $this->assertForeignKeysOn();
    }

    public function testForeignKeysAreEnforcedAfterFixtureLoad(): void
    {
        $this->databaseTool->loadFixtures([MaintenanceWarningFixtures::class]);
        $this->assertForeignKeysOn();

        // A second load of the same fixtures is served from Liip's SQLite backup cache
        $this->databaseTool->loadFixtures([MaintenanceWarningFixtures::class]);
        $this->assertForeignKeysOn();
    }

    public function testForeignKeysAreEnforcedAfterReconnect(): void
    {
        $this->connection->close();
        $this->assertForeignKeysOn();
    }

    public function testOrphanInsertIsRejected(): void
    {
        $this->databaseTool->loadFixtures([]);

        $this->expectException(ForeignKeyConstraintViolationException::class);
        $this->connection->insert('notification_interception_company_name', [
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Orphan Ltd',
            'notification_interception_id' => '00000000-0000-0000-0000-00000000dead',
        ]);
    }

    private function assertForeignKeysOn(): void
    {
        $this->assertSame(1, (int) $this->connection->fetchOne('PRAGMA foreign_keys'));
    }
}
