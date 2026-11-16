<?php

namespace App\Tests\MySQL;

use App\Tests\EventListener\InternationalSurvey\DataConsistencyListenerCrashRegressionTest as SqliteVersion;

/**
 * The HGV-zero vehicle-delete crash (production FK error 1451 on trip save) on MySQL, which enforces foreign keys the
 * way production does. Restoring the old predicate (HGV estimate 0 => submit without journeys) makes this fail with
 * a ForeignKeyConstraintViolationException.
 */
class DataConsistencyListenerCrashRegressionTest extends SqliteVersion
{
    use RunsOnMySql;
}
