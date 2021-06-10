<?php

use App\Kernel;
use App\Utility\RemoteActions;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/_ah/warmup':
        RemoteActions::runProcess(['../bin/console', 'cache:clear']);
        echo "Warmup successful";
        exit;

    case '/_util/pre-install' :
        try {
            RemoteActions::denyAccessUnlessHmacPasses($_GET['hmac'] ?? '', $_GET['timestamp'] ?? 0, 'pre-install');
            echo RemoteActions::preInstall('../');
        } catch (Throwable $e) {
            RemoteActions::handleException($e);
        }
        exit;

    case '/_util/post-install' :
        try {
            RemoteActions::denyAccessUnlessHmacPasses($_GET['hmac'] ?? '', $_GET['timestamp'] ?? 0, 'post-install');
            echo RemoteActions::postInstall('../');
        } catch (Throwable $e) {
            RemoteActions::handleException($e);
        }
        exit;
//
//    case '/_util/pre-install' :
//        if (_denyAccessUnlessHmacPasses('pre-install')) {
//            $migrationStatus = _runProcess(['../bin/console', 'doctrine:migrations:status'], false);
//            echo $migrationStatus;
//            preg_match_all('#(?:\|\s+New\s+\|\s+(?<value>\d+))#', $migrationStatus, $matches);
//
//            // New migrations waiting
//            if ($migrationsCount = intval($matches['value'][0] ?? 0) > 0) {
//                echo "{$migrationsCount} migration(s) pending\n";
//                $lockStatus = _runProcess(['../bin/console', 'rfs:maintenance-mode:status'], false);
//                if (stripos($lockStatus, 'not active') !== false) {
//                    header('HTTP/1.0 500 Internal Error');
//                    echo "Maintenance lock is NOT active! Activate the maintenance lock before re-deploying.\n";
//                    echo "Pre-install checks have failed.\n";
//                    exit;
//                } else {
//                    echo "Maintenance mode is active\n";
//                }
//            } else {
//                echo "There are NO pending migrations\n";
//            }
//            echo "Pre-install checks have passed.\n";
//        }
//        exit;
//
//    case '/_util/post-install' :
//        if (_denyAccessUnlessHmacPasses('post-install')) {
//            _runProcess(['../bin/console', 'messenger:stop-workers']); // stop running messenger workers
//            _runProcess(['../bin/console', 'doctrine:migrations:migrate', '-n']); // run any pending migrations
//        }
//        exit;
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

Request::setTrustedProxies(['127.0.0.1', 'REMOTE_ADDR'], Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);


//function _denyAccessUnlessHmacPasses($action): bool
//{
//    $requestTimestamp = $_GET['timestamp'] ?? 0;
//    if (
//        abs(time() - $requestTimestamp) <= 5
//        && hash_equals(hash_hmac('sha256', "{$action}:{$requestTimestamp}", "{$_ENV['APP_SECRET']}"), $_GET['hmac'] ?? '')
//    ) {
//        return true;
//    }
//    header('HTTP/1.0 403 Forbidden');
//    echo 'forbidden';
//    exit;
//}
//
//function _runProcess($command, $showOutput = true): string
//{
//    $process = new Process($command);
//    if ($showOutput) {
//        echo "=== running command \"" . implode(" ", $command) . "\" ===\n";
//    }
//    $returnValue = $process->run(function ($type, $buffer) use ($showOutput) {
//        if ($showOutput) {
//            echo $buffer;
//        }
//    });
//    if ($returnValue !== 0) {
//        header('HTTP/1.0 500 Internal error');
//        echo "HALT: There was a problem";
//        exit($returnValue);
//    }
//    if ($showOutput) {
//        echo "=== finished ===\n\n";
//    }
//    return $process->getOutput();
//}
