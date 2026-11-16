<?php

namespace App\Tests\MySQL;

use App\Tests\Support\MySqlTestDatabaseGuard;
use PHPUnit\Framework\Attributes\Before;

/**
 * Fails a tests/MySQL test that isn't running against MySQL (e.g. `phpunit --testsuite mysql` on the SQLite test
 * database), rather than letting it pass there: several of these re-run SQLite tests unchanged, so they would.
 * Run the suite with bin/test-mysql.
 */
trait RunsOnMySql
{
    #[Before]
    protected function failUnlessRunningOnMySql(): void
    {
        $databaseUrl = (string) ($_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? '');

        if (!MySqlTestDatabaseGuard::usesMySql($databaseUrl)) {
            $this->fail('tests/MySQL must run against MySQL: use bin/test-mysql (see docs/MySqlTests.md)');
        }
    }
}
