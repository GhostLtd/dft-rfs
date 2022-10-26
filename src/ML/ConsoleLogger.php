<?php

namespace App\ML;

use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleLogger
{
    public const LEVEL_ERROR = 1;
    public const LEVEL_INVALID_TEXT = 2;
    public const LEVEL_INVALID_CODE = 4;
    public const LEVEL_INVALID = self::LEVEL_INVALID_TEXT + self::LEVEL_INVALID_CODE;
    public const LEVEL_DUPLICATE = 8;
    public const LEVEL_CONTRADICTORY = 16;

    protected SymfonyStyle $io;
    protected int $logLevel;

    public function __construct(SymfonyStyle $io, int $logLevel=1)
    {
        $this->io = $io;
        $this->logLevel = $logLevel;
    }

    public function log(string $message, int $logLevel): void
    {
        if (($logLevel & $this->logLevel) !== 0) {
            if ($logLevel === self::LEVEL_ERROR) {
                $this->io->error($message);
            } else {
                $this->io->warning($message);
            }
        }
    }
}