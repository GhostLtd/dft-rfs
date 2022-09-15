<?php

use App\Kernel;
use App\Utility\RemoteActions;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/_ah/warmup':
        // commented out as it wasn't working and just spamming the log with errors
        // It seems there's a file permission error, probably related to spinning up of a new instance
        //RemoteActions::runProcess(['../bin/console', 'cache:clear']);
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
