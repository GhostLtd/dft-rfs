<?php

namespace App\Tests\MySQL;

use App\Tests\Repository\AuditLog\AuditLogStateQueriesTest as SqliteVersion;

/**
 * The audit log's state queries, which read its JSON through GHOST_JSON_GET, return the same results on MySQL.
 *
 * N.B. This wouldn't catch GHOST_JSON_GET going back to JSON_EXTRACT. The queries only compare with "=", and MySQL
 * compares JSON_EXTRACT's (quoted) result with a string as JSON, so they'd match either way.
 */
class AuditLogStateQueriesTest extends SqliteVersion
{
    use RunsOnMySql;
}
