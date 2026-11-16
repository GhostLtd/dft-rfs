<?php

namespace App\Tests\MySQL;

use App\Tests\Utility\Notification\SmsAllowanceTest as SqliteVersion;

/**
 * The SMS counters reserve units with conditional UPDATEs and rely on the affected-row count, which MySQL's driver
 * reports as rows *changed* (not matched). The same cases, including at the cap, must hold on MySQL.
 */
class SmsAllowanceTest extends SqliteVersion
{
    use RunsOnMySql;
}
