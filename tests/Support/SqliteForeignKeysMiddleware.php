<?php

namespace App\Tests\Support;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * Turns on foreign-key enforcement for SQLite connections, matching production's MySQL.
 *
 * SQLite only enforces foreign keys on a connection that has issued `PRAGMA foreign_keys = ON`.
 * DBAL's own EnableForeignKeys middleware issues the PRAGMA whatever the driver (a syntax error on
 * MySQL), and checking the driver's class in wrap() fails once another middleware (e.g.
 * DatabaseActivityRecorder) has wrapped it, so this checks the connection parameters on connect.
 *
 * Registered for the test environment in config/services_test.yaml.
 */
class SqliteForeignKeysMiddleware implements Middleware
{
    public const array SQLITE_DRIVERS = ['pdo_sqlite', 'sqlite3'];

    #[\Override]
    public function wrap(Driver $driver): Driver
    {
        return new class($driver) extends AbstractDriverMiddleware {
            #[\Override]
            public function connect(array $params): Connection
            {
                $connection = parent::connect($params);

                if (in_array($params['driver'] ?? null, SqliteForeignKeysMiddleware::SQLITE_DRIVERS, true)) {
                    $connection->exec('PRAGMA foreign_keys = ON');
                }

                return $connection;
            }
        };
    }
}
