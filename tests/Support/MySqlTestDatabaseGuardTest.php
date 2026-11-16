<?php

namespace App\Tests\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MySqlTestDatabaseGuardTest extends TestCase
{
    private const array SAFE_GRANTS = [
        'GRANT USAGE ON *.* TO `rfstest`@`%`',
        'GRANT ALL PRIVILEGES ON `rfstest`.* TO `rfstest`@`%`',
    ];

    public function testAcceptsTheDisposableComposeDatabase(): void
    {
        $this->expectNotToPerformAssertions();
        MySqlTestDatabaseGuard::check('rfs-test-mysql', 'rfstest', self::SAFE_GRANTS);
    }

    public static function dataRefusesOtherHosts(): array
    {
        return [
            'dev mysql container' => ['mysql'],
            'localhost' => ['localhost'],
            'loopback' => ['127.0.0.1'],
            'cloud sql proxy' => ['cloud-sql-proxy'],
            'prefix match' => ['rfs-test-mysql.example.com'],
            'empty' => [''],
        ];
    }

    #[DataProvider('dataRefusesOtherHosts')]
    public function testRefusesOtherHosts(string $host): void
    {
        $this->expectExceptionMessageMatches('/host/');
        MySqlTestDatabaseGuard::check($host, 'rfstest', self::SAFE_GRANTS);
    }

    public static function dataRefusesOtherDatabaseNames(): array
    {
        return [
            'dev database' => ['dft-rfs'],
            'underscore (wildcard in grants)' => ['rfs_test'],
            'suffix' => ['rfstest2'],
            'empty' => [''],
        ];
    }

    #[DataProvider('dataRefusesOtherDatabaseNames')]
    public function testRefusesOtherDatabaseNames(string $database): void
    {
        $this->expectExceptionMessageMatches('/database/');
        MySqlTestDatabaseGuard::check('rfs-test-mysql', $database, self::SAFE_GRANTS);
    }

    public static function dataRefusesBroaderGrants(): array
    {
        return [
            'global privilege' => ['GRANT SELECT ON *.* TO `rfstest`@`%`'],
            'global all' => ['GRANT ALL PRIVILEGES ON *.* TO `root`@`%` WITH GRANT OPTION'],
            'other schema' => ['GRANT ALL PRIVILEGES ON `dft-rfs`.* TO `rfstest`@`%`'],
            'wildcard schema (unescaped underscore)' => ['GRANT ALL PRIVILEGES ON `rfs_test`.* TO `rfstest`@`%`'],
            'wildcard schema (percent)' => ['GRANT ALL PRIVILEGES ON `rfs%`.* TO `rfstest`@`%`'],
            'table in another schema' => ['GRANT SELECT ON `mysql`.`user` TO `rfstest`@`%`'],
            'table-level grant' => ['GRANT SELECT ON `rfstest`.`survey` TO `rfstest`@`%`'],
            'role grant' => ['GRANT `cloudsqlsuperuser`@`%` TO `rfstest`@`%`'],
            'unrecognised line' => ['something unexpected'],
        ];
    }

    #[DataProvider('dataRefusesBroaderGrants')]
    public function testRefusesBroaderGrants(string $extraGrant): void
    {
        $this->expectExceptionMessageMatches('/grant/i');
        MySqlTestDatabaseGuard::check('rfs-test-mysql', 'rfstest', [...self::SAFE_GRANTS, $extraGrant]);
    }

    public static function dataDatabaseUrls(): array
    {
        return [
            'mysql' => ['mysql://u:p@rfs-test-mysql/rfstest?serverVersion=8.0.18', true],
            'mysql2 (DoctrineBundle maps it to pdo_mysql)' => ['mysql2://u:p@mysql/dft-rfs', true],
            'pdo-mysql' => ['pdo-mysql://u:p@mysql/dft-rfs', true],
            'mysqli' => ['mysqli://u:p@mysql/dft-rfs', true],
            'sqlite' => ['sqlite:////tmp/test.sqlite', false],
            'sqlite3' => ['sqlite3:////tmp/test.sqlite', false],
            'pdo-sqlite' => ['pdo-sqlite:////tmp/test.sqlite', false],
        ];
    }

    #[DataProvider('dataDatabaseUrls')]
    public function testUsesMySqlDecidesByDriverNotUrlPrefix(string $databaseUrl, bool $expected): void
    {
        $this->assertSame($expected, MySqlTestDatabaseGuard::usesMySql($databaseUrl));
    }

    public function testUsesMySqlRefusesOtherDatabases(): void
    {
        // The SQLite bootstrap drops the database too, so anything unrecognised must not fall through to it
        $this->expectExceptionMessageMatches('/neither SQLite nor MySQL/');
        MySqlTestDatabaseGuard::usesMySql('postgresql://u:p@db/app');
    }

    public static function dataConnectionOverrides(): array
    {
        $mysqlDriverClass = urlencode(\Doctrine\DBAL\Driver\PDO\MySQL\Driver::class);

        return [
            // Doctrine's DriverManager gives driverClass precedence over driver
            'driverClass on a mysql url' => ["mysql://u:p@mysql/dft-rfs?driver=pdo_sqlite&driverClass={$mysqlDriverClass}"],
            'driverClass on a sqlite url' => ["sqlite:////tmp/test.sqlite?driverClass={$mysqlDriverClass}"],
            'wrapperClass' => ['mysql://u:p@rfs-test-mysql/rfstest?wrapperClass=' . urlencode(\Doctrine\DBAL\Connection::class)],
            'unix socket' => ['mysql://u:p@rfs-test-mysql/rfstest?unix_socket=/var/run/mysqld/mysqld.sock'],
        ];
    }

    #[DataProvider('dataConnectionOverrides')]
    public function testUsesMySqlRefusesConnectionOverrides(string $databaseUrl): void
    {
        // Only plain URLs are classified: anything that could route the connection elsewhere is refused
        $this->expectExceptionMessageMatches('/unsupported connection parameter/');
        MySqlTestDatabaseGuard::usesMySql($databaseUrl);
    }

    public static function dataInjectedDsnValues(): array
    {
        // PDO's MySQL DSN is "host=...;port=...;dbname=...;charset=...", so a ";" in a value adds or overrides a key
        return [
            'port' => ['mysql://u:p@rfs-test-mysql/rfstest?port=' . urlencode('3306;host=elsewhere.invalid')],
            'non-numeric port' => ['mysql://u:p@rfs-test-mysql/rfstest?port=abc'],
            'charset' => ['mysql://u:p@rfs-test-mysql/rfstest?charset=' . urlencode('utf8mb4;host=elsewhere.invalid')],
            'charset with spaces' => ['mysql://u:p@rfs-test-mysql/rfstest?charset=' . urlencode('utf8 mb4')],
        ];
    }

    #[DataProvider('dataInjectedDsnValues')]
    public function testUsesMySqlRefusesInjectedDsnValues(string $databaseUrl): void
    {
        $this->expectExceptionMessageMatches('/invalid (port|charset)/');
        MySqlTestDatabaseGuard::usesMySql($databaseUrl);
    }

    #[DataProvider('dataInjectedDsnValues')]
    public function testCheckDatabaseUrlRefusesInjectedDsnValuesBeforeConnecting(string $databaseUrl): void
    {
        // Refused before any connection attempt (none of these hosts would resolve)
        $this->expectExceptionMessageMatches('/invalid (port|charset)/');
        MySqlTestDatabaseGuard::checkDatabaseUrl($databaseUrl);
    }

    public function testUsesMySqlAllowsTheUsualParameters(): void
    {
        $this->assertTrue(MySqlTestDatabaseGuard::usesMySql('mysql://u:p@rfs-test-mysql:3306/rfstest?serverVersion=8.0.18&charset=utf8mb4'));
        $this->assertFalse(MySqlTestDatabaseGuard::usesMySql('sqlite:///:memory:'));
    }

    public static function dataAgreeingSources(): array
    {
        $url = 'sqlite:////tmp/test.sqlite';

        return [
            'all three' => [['DATABASE_URL' => $url], ['DATABASE_URL' => $url], $url, $url],
            'server only' => [['DATABASE_URL' => $url], [], false, $url],
            'env only' => [[], ['DATABASE_URL' => $url], false, $url],
            'process env only' => [[], [], $url, $url],
            'none' => [[], [], false, ''],
        ];
    }

    #[DataProvider('dataAgreeingSources')]
    public function testResolveDatabaseUrlAcceptsAgreeingSources(array $server, array $env, string|false $processEnv, string $expected): void
    {
        $this->assertSame($expected, MySqlTestDatabaseGuard::resolveDatabaseUrl($server, $env, $processEnv));
    }

    public static function dataConflictingSources(): array
    {
        $checked = 'mysql://u:p@rfs-test-mysql/rfstest';
        $other = 'mysql://u:p@mysql/dft-rfs';

        return [
            // e.g. a phpunit.xml <server> value over a real environment variable: the subprocesses read $_ENV first
            'server vs env' => [['DATABASE_URL' => $checked], ['DATABASE_URL' => $other], false],
            'server vs process env' => [['DATABASE_URL' => $checked], [], $other],
            'env vs process env' => [[], ['DATABASE_URL' => $checked], $other],
        ];
    }

    #[DataProvider('dataConflictingSources')]
    public function testResolveDatabaseUrlRefusesConflictingSources(array $server, array $env, string|false $processEnv): void
    {
        $this->expectExceptionMessageMatches('/DATABASE_URL differs/');
        MySqlTestDatabaseGuard::resolveDatabaseUrl($server, $env, $processEnv);
    }

    public function testRefusesWhenNoGrantsAreVisible(): void
    {
        $this->expectExceptionMessageMatches('/grant/i');
        MySqlTestDatabaseGuard::check('rfs-test-mysql', 'rfstest', []);
    }
}
