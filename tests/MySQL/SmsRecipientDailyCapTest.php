<?php

namespace App\Tests\MySQL;

use App\Tests\Utility\Notification\SmsRecipientDailyCapTest as SqliteVersion;

/**
 * The SMS counters reserve units with conditional UPDATEs and rely on the affected-row count, which MySQL's driver
 * reports as rows *changed* (not matched). The same cases, including at the cap, must hold on MySQL.
 */
class SmsRecipientDailyCapTest extends SqliteVersion
{
    use RunsOnMySql;
}
