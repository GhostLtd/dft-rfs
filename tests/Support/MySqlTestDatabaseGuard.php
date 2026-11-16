<?php

namespace App\Tests\Support;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

/**
 * Stops the MySQL test bootstrap (which drops and recreates the database) from running against
 * anything except the disposable Compose database from compose.mysql-tests.yaml.
 *
 * APP_ENV=test protects nothing on its own, so this checks three things:
 *
 * - the host is the Compose service name (never the long-lived dev "mysql" container)
 * - the database is exactly "rfstest" (no "_" or "%": both are wildcards in MySQL database grants,
 *   and the mysql image's entrypoint doesn't escape them)
 * - the connecting user has no privileges beyond that one database. "GRANT USAGE ON *.*" is
 *   allowed because every account has it and it grants nothing.
 */
class MySqlTestDatabaseGuard
{
    public const string HOST = 'rfs-test-mysql';
    public const string DATABASE = 'rfstest';

    private const string USAGE_GRANT = '/^GRANT USAGE ON \*\.\* TO \S+$/';
    private const array MYSQL_DRIVERS = ['pdo_mysql', 'mysqli'];
    private const array SQLITE_DRIVERS = ['pdo_sqlite', 'sqlite3'];

    /**
     * The connection parameters a test DATABASE_URL may set. Anything else (driverClass, wrapperClass, unix_socket...)
     * could route the connection somewhere these checks don't see, so it's refused.
     */
    private const array ALLOWED_PARAMETERS = ['driver', 'host', 'port', 'user', 'password', 'dbname', 'path', 'memory', 'serverVersion', 'charset'];

    /**
     * The one DATABASE_URL the bootstrap checks and then hands to every command and test kernel.
     *
     * The bootstrap reads $_SERVER, Symfony's Process and env processor read $_ENV first, and getenv() is a third
     * source: if they disagree, the checked URL might not be the one that gets reset, so that's refused.
     */
    public static function resolveDatabaseUrl(array $server, array $env, string|false $processEnv): string
    {
        $values = array_filter(
            ['$_SERVER' => $server['DATABASE_URL'] ?? null, '$_ENV' => $env['DATABASE_URL'] ?? null, 'getenv()' => $processEnv],
            fn(mixed $value) => is_string($value) && $value !== '',
        );

        if (count(array_unique($values)) > 1) {
            self::refuse('DATABASE_URL differs between ' . implode(', ', array_keys($values)));
        }

        return (string) (reset($values) ?: '');
    }

    /**
     * Whether DATABASE_URL points at MySQL, decided by the driver Doctrine will use (so "mysql2://", "pdo-mysql://"
     * and "mysqli://" count as MySQL, not just "mysql://"). Anything neither SQLite nor MySQL is refused, as the
     * SQLite bootstrap drops its database too.
     */
    public static function usesMySql(string $databaseUrl): bool
    {
        $params = self::parse($databaseUrl);
        $driver = $params['driver'] ?? null;

        return match (true) {
            in_array($driver, self::MYSQL_DRIVERS, true) => true,
            in_array($driver, self::SQLITE_DRIVERS, true) => false,
            default => self::refuse(sprintf('DATABASE_URL is neither SQLite nor MySQL (driver "%s")', $driver ?? '')),
        };
    }

    /**
     * Checks DATABASE_URL, reading the grants from the server. The host and database name are checked before
     * connecting anywhere; the connection doesn't select the database, as it may not exist yet.
     */
    public static function checkDatabaseUrl(string $databaseUrl): void
    {
        $params = self::parse($databaseUrl);
        self::checkTarget((string) ($params['host'] ?? ''), (string) ($params['dbname'] ?? ''));

        unset($params['dbname']);
        $connection = DriverManager::getConnection($params);
        try {
            $grants = $connection->fetchFirstColumn('SHOW GRANTS FOR CURRENT_USER()');
        } finally {
            $connection->close();
        }

        self::checkGrants(array_map('strval', $grants));
    }

    /**
     * @param list<string> $grants The rows of SHOW GRANTS FOR CURRENT_USER()
     * @throws \RuntimeException
     */
    public static function check(string $host, string $database, array $grants): void
    {
        self::checkTarget($host, $database);
        self::checkGrants($grants);
    }

    private static function checkTarget(string $host, string $database): void
    {
        if ($host !== self::HOST) {
            self::refuse(sprintf('host "%s" is not the disposable Compose service "%s"', $host, self::HOST));
        }

        if ($database !== self::DATABASE) {
            self::refuse(sprintf('database "%s" is not "%s"', $database, self::DATABASE));
        }
    }

    /** @param list<string> $grants */
    private static function checkGrants(array $grants): void
    {
        $databaseGrant = sprintf('/^GRANT .+ ON `%s`\.\* TO \S+( WITH GRANT OPTION)?$/', self::DATABASE);
        $hasDatabaseGrant = false;

        foreach ($grants as $grant) {
            if (preg_match(self::USAGE_GRANT, $grant)) {
                continue;
            }

            if (preg_match($databaseGrant, $grant)) {
                $hasDatabaseGrant = true;
                continue;
            }

            self::refuse(sprintf('grant reaches beyond `%s`: %s', self::DATABASE, $grant));
        }

        if (!$hasDatabaseGrant) {
            self::refuse(sprintf('no grant on `%s`.* is visible for the current user', self::DATABASE));
        }
    }

    /** Parses the URL as DoctrineBundle does, refusing anything that could route the connection elsewhere */
    private static function parse(string $databaseUrl): array
    {
        $params = (new DsnParser(ConnectionFactory::DEFAULT_SCHEME_MAP))->parse($databaseUrl);

        $unsupported = array_diff(array_keys($params), self::ALLOWED_PARAMETERS);
        if ($unsupported !== []) {
            self::refuse('unsupported connection parameter(s) in DATABASE_URL: ' . implode(', ', $unsupported));
        }

        // PDO's MySQL DSN joins these as "key=value;...", so a ";" in a value could add or override a key (e.g. the
        // host checked below). host and dbname must match exactly anyway; user/password aren't part of the DSN.
        if (isset($params['port']) && !preg_match('/^\d{1,5}$/', (string) $params['port'])) {
            self::refuse('invalid port in DATABASE_URL');
        }
        if (isset($params['charset']) && !preg_match('/^[A-Za-z0-9_]+$/', (string) $params['charset'])) {
            self::refuse('invalid charset in DATABASE_URL');
        }

        return $params;
    }

    private static function refuse(string $reason): never
    {
        throw new \RuntimeException("Refusing to reset the MySQL test database: {$reason}");
    }
}
