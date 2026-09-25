<?php

namespace App\Tests\Utility;

use App\Utility\DatabaseDescription;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DatabaseDescriptionTest extends TestCase
{
    public function testDescribe(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_mysql', 'host' => 'mysql', 'port' => 3306, 'dbname' => 'rfs_rehearsal']);
        self::assertSame('rfs_rehearsal @ mysql:3306', (new DatabaseDescription($connection))->describe());
    }

    public function testDescribeSqlite(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => '/tmp/test.sqlite']);
        self::assertSame('sqlite /tmp/test.sqlite', (new DatabaseDescription($connection))->describe());
    }
}
