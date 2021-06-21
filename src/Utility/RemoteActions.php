<?php


namespace App\Utility;


use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

class RemoteActions
{
    const HMAC_TIMEOUT = 30;

    public static function preInstall($kernelDir = '')
    {
        $output = "";
        $migrationStatus = self::runProcess(["{$kernelDir}bin/console", 'doctrine:migrations:status'], false);
        preg_match_all('#(?:\|\s+New\s+\|\s+(?<value>\d+))#', $migrationStatus, $matches);

        // New migrations waiting
        if ($migrationsCount = intval($matches['value'][0] ?? 0) > 0) {
            $output .= "{$migrationsCount} migration(s) pending\n";
            $lockStatus = self::runProcess(["{$kernelDir}bin/console", 'rfs:maintenance-mode:status'], false);
            if (stripos($lockStatus, 'not active') !== false) {
                throw new HttpException(500, "{$output}Maintenance lock is NOT active! Activate the maintenance lock before re-deploying.\nPre-install checks have failed.\n");
            } else {
                $output .= "Maintenance mode is active\n";
            }
        } else {
            $output .= "There are NO pending migrations\n";
        }
        $output .= "Pre-install checks have passed.\n";

        return $output;
    }

    public static function postInstall($kernelDir = '')
    {
        $output = self::runProcess(["{$kernelDir}bin/console", 'messenger:stop-workers']); // stop running messenger workers
        $output .= self::runProcess(["{$kernelDir}bin/console", 'doctrine:migrations:migrate', '-n']); // run any pending migrations
        $output .= "Post-install script completed successfully.\n";
        return $output;
    }

    public static function denyAccessUnlessHmacPasses($hmac, $timestamp, $action): bool
    {
        $currentTime = time();
        if (
            abs($currentTime - $timestamp) <= self::HMAC_TIMEOUT
            && hash_equals(hash_hmac('sha256', "{$action}:{$timestamp}", "{$_ENV['APP_SECRET']}"), $hmac)
        ) {
            return true;
        }
        throw new AccessDeniedHttpException("HMAC: {$hmac}\nTime: {$currentTime}\nTimestamp: {$timestamp}\nAction: {$action}");
    }

    public static function runProcess($command, $addInformationalOutput = true): string
    {
        $output = "";
        $process = new Process($command);
        if ($addInformationalOutput) {
            $output .= "=== running command \"" . implode(" ", $command) . "\" ===\n";
        }
        $returnValue = $process->run(function ($type, $buffer) use (&$output) {
            $output .= $buffer;
        });
        if ($returnValue !== 0) {
            throw new HttpException(500, "HALT! There was a problem: {$returnValue}");
        }
        if ($addInformationalOutput) {
            $output .= "=== finished ===\n\n";
        }
        return $output;
    }

    public static function handleException(\Throwable $originalException)
    {
        if ($originalException instanceof HttpException) {
            header("HTTP/1.0 {$originalException->getStatusCode()}");
        } else {
            header("HTTP/1.0 500 Unhandled error");
        }
        echo $originalException->getMessage();
        exit;
    }
}