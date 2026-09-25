<?php

namespace App\Utility;

use Doctrine\DBAL\Connection;

/** Describes the target database for console output, without connecting to it. */
class DatabaseDescription
{
    public function __construct(private readonly Connection $connection) {}

    /** Returns "dbname @ host:port" or "sqlite path", using "?" for missing connection details. */
    public function describe(): string
    {
        $params = $this->connection->getParams();
        if (str_contains((string) ($params['driver'] ?? ''), 'sqlite')) {
            return 'sqlite '.($params['path'] ?? 'memory');
        }

        return ($params['dbname'] ?? '?').' @ '.($params['host'] ?? '?').(isset($params['port']) ? ':'.$params['port'] : '');
    }
}
