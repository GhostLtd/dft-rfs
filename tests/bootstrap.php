<?php

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

$bootstrapCommands = [
    ['bin/console', '--env=test', 'd:d:d', '-f'],
    ['bin/console', '--env=test', 'd:s:c'],
    ['bin/console', '--env=test', 'messenger:setup'],
];

foreach($bootstrapCommands as $args) {
    $process = new Process($args);
    $process->run();

    if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
    }
}

echo "Bootstrap successful...\n";