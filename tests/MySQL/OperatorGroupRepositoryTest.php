<?php

namespace App\Tests\MySQL;

use App\Tests\Repository\OperatorGroupRepositoryTest as SqliteVersion;

/**
 * The operator group prefix checks on MySQL. The repository uses LOWER() so that SQLite matches case-insensitively
 * like production; this makes sure the same cases hold under production's collation.
 */
class OperatorGroupRepositoryTest extends SqliteVersion
{
    use RunsOnMySql;
}
