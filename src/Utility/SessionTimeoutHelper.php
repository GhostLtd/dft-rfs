<?php

namespace App\Utility;

use DateTime;

class SessionTimeoutHelper
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(protected int $warningThreshold = 300)
    {
    }

    public function getExpiryTime(): string
    {
        $maxLifetime = $this->getMaxLifetime();
        return (new DateTime("{$maxLifetime} seconds"))->format(self::DATE_FORMAT);
    }

    public function getWarningTime(): string
    {
        $maxLifetime = $this->getMaxLifetime() - $this->warningThreshold;
        return (new DateTime("{$maxLifetime} seconds"))->format(self::DATE_FORMAT);
    }

    protected function getMaxLifetime(): int
    {
        return intval(ini_get('session.gc_maxlifetime'));
    }
}