<?php

use App\Tests\Support\MySqlTestDatabaseGuard;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Clear test cache as APP_DEBUG is false
// See: https://maks-rafalko.github.io/blog/2021-11-21/symfony-tests-performance/#set-app-debug-false
(new Filesystem())->remove([__DIR__ . '/../var/cache/test']);

// One DATABASE_URL for everything below: refuse if its sources disagree, then make them all (and so every console
// command and test kernel) use the value that gets checked
$databaseUrl = MySqlTestDatabaseGuard::resolveDatabaseUrl($_SERVER, $_ENV, getenv('DATABASE_URL'));
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $databaseUrl;
putenv("DATABASE_URL={$databaseUrl}");

// Decided by Doctrine's driver, not the URL prefix: any MySQL URL must go through the guard below
$isMySql = MySqlTestDatabaseGuard::usesMySql($databaseUrl);

if ($isMySql) {
    // The MySQL test suite (bin/test-mysql) builds the schema the way production does: migrations, then the
    // views and functions from sql/. It drops the database first, so refuse anything but the disposable one.
    MySqlTestDatabaseGuard::checkDatabaseUrl($databaseUrl);

    $bootstrapCommands = [
        ['bin/console', '--env=test', 'd:d:d', '-f'],
        ['bin/console', '--env=test', 'd:d:c'],
        ['bin/console', '--env=test', 'd:m:m', '-n'],
        ['bin/console', '--env=test', 'app:views:apply'],
        ['bin/console', '--env=test', 'd:s:validate', '--skip-mapping'],
        ['bin/console', '--env=test', 'messenger:setup'],
    ];
} else {
    $bootstrapCommands = [
        ['bin/console', '--env=test', 'd:d:d', '-f'],
        ['bin/console', '--env=test', 'd:s:c'],
        ['bin/console', '--env=test', 'app:views:apply'],
        ['bin/console', '--env=test', 'messenger:setup'],
    ];
}

foreach($bootstrapCommands as $args) {
    $process = new Process($args, null, ['DATABASE_URL' => $databaseUrl]);
    $process->setTimeout(null);
    $process->run();

    if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
    }
}

if ($isMySql) {
    // Show what this run actually ran against, through the app's own connection (ServerSettingsTest asserts them)
    $process = new Process(['bin/console', '--env=test', 'dbal:run-sql', 'SELECT @@version, @@sql_mode, '
        . '@@collation_server, @@collation_connection, @@character_set_connection'], null, ['DATABASE_URL' => $databaseUrl]);
    $process->mustRun();
    echo $process->getOutput();
}

echo "Bootstrap successful...\n";
