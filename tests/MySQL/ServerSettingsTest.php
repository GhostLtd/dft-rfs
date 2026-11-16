<?php

namespace App\Tests\MySQL;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The MySQL test suite is only as good as its likeness to production (Cloud SQL, MySQL 8.0.18-google).
 *
 * The expected values were read from production on 2026-11-16: server variables from SHOW GLOBAL VARIABLES, and
 * the connection settings through the app itself (bin/console dbal:run-sql), since PHP's mysqlnd negotiates
 * utf8mb4_general_ci whatever the server default. Of the variables that affect query results or schema behaviour,
 * the four flagged below differ from stock mysql:8.0.18 and are set by compose.mysql-tests.yaml; if this test
 * fails, the stack has drifted from production. See docs/MySqlTests.md.
 */
class ServerSettingsTest extends KernelTestCase
{
    use RunsOnMySql;

    public static function dataProductionSettings(): array
    {
        return [
            'sql_mode' => ['sql_mode', 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'],
            'collation_server' => ['collation_server', 'utf8mb4_0900_ai_ci'],
            'character_set_server' => ['character_set_server', 'utf8mb4'],
            'collation_connection' => ['collation_connection', 'utf8mb4_general_ci'],
            'character_set_connection' => ['character_set_connection', 'utf8mb4'],
            'time_zone' => ['time_zone', 'SYSTEM'],
            'system_time_zone' => ['system_time_zone', 'UTC'],
            'transaction_isolation' => ['transaction_isolation', 'REPEATABLE-READ'],
            'default_week_format' => ['default_week_format', '0'],
            'group_concat_max_len' => ['group_concat_max_len', '1024'],
            'lower_case_table_names' => ['lower_case_table_names', '0'],
            'explicit_defaults_for_timestamp' => ['explicit_defaults_for_timestamp', '1'],
            'foreign_key_checks' => ['foreign_key_checks', '1'],
            // Set in compose.mysql-tests.yaml (stock 8.0.18 differs)
            'log_bin_trust_function_creators' => ['log_bin_trust_function_creators', '1'],
            'max_allowed_packet' => ['max_allowed_packet', (string) (32 * 1024 * 1024)],
            'local_infile' => ['local_infile', '1'],
            'default_authentication_plugin' => ['default_authentication_plugin', 'mysql_native_password'],
        ];
    }

    #[DataProvider('dataProductionSettings')]
    public function testMatchesProduction(string $variable, string $expected): void
    {
        $this->assertSame($expected, (string) $this->getConnection()->fetchOne("SELECT @@{$variable}"));
    }

    public function testVersionMatchesProduction(): void
    {
        // Production reports "8.0.18-google"; Google's build isn't available, so stock 8.0.18 is the closest match
        $this->assertSame('8.0.18', $this->getConnection()->fetchOne('SELECT @@version'));
    }

    private function getConnection(): Connection
    {
        static::bootKernel();
        return static::getContainer()->get('doctrine.dbal.default_connection');
    }
}
