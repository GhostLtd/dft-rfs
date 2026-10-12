<?php

namespace App\Tests\Support;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

/**
 * DBAL middleware that records driver connections and SQL while recording is on (and does nothing otherwise).
 *
 * Unlike the Doctrine profiler this works with debug off, and it also sees a connection that is opened
 * without any SQL being run. Registered for the whole test environment in config/services_test.yaml.
 *
 * Callers must stop() and reset() in a `finally`, as the state is static.
 */
class DatabaseActivityRecorder implements Middleware
{
    private static bool $recording = false;
    private static int $connections = 0;
    /** @var list<string> */
    private static array $queries = [];

    public static function start(): void
    {
        self::reset();
        self::$recording = true;
    }

    public static function stop(): void
    {
        self::$recording = false;
    }

    public static function reset(): void
    {
        self::$connections = 0;
        self::$queries = [];
    }

    public static function getConnectionCount(): int
    {
        return self::$connections;
    }

    /** @return list<string> */
    public static function getQueries(): array
    {
        return self::$queries;
    }

    public static function recordConnection(): void
    {
        if (self::$recording) {
            self::$connections++;
        }
    }

    public static function recordQuery(string $sql): void
    {
        if (self::$recording) {
            self::$queries[] = $sql;
        }
    }

    #[\Override]
    public function wrap(Driver $driver): Driver
    {
        return new class($driver) extends AbstractDriverMiddleware {
            #[\Override]
            public function connect(array $params): Connection
            {
                DatabaseActivityRecorder::recordConnection();

                return new class(parent::connect($params)) extends AbstractConnectionMiddleware {
                    #[\Override]
                    public function prepare(string $sql): Statement
                    {
                        DatabaseActivityRecorder::recordQuery($sql);
                        return parent::prepare($sql);
                    }

                    #[\Override]
                    public function query(string $sql): Result
                    {
                        DatabaseActivityRecorder::recordQuery($sql);
                        return parent::query($sql);
                    }

                    #[\Override]
                    public function exec(string $sql): int|string
                    {
                        DatabaseActivityRecorder::recordQuery($sql);
                        return parent::exec($sql);
                    }
                };
            }
        };
    }
}
