<?php

namespace App\Tests\MySQL;

use App\Tests\Utility\Reminder\DomesticRemindersTest as SqliteVersion;

/**
 * Reminder selection compares survey dates against cut-offs; on MySQL those are DATE/DATETIME comparisons, on SQLite
 * string comparisons. The same cases must select the same surveys under production's database.
 */
class DomesticRemindersTest extends SqliteVersion
{
    use RunsOnMySql;
}
