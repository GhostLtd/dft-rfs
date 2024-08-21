<?php

namespace App\ML;

class Stats
{
    public const CONTRADICTORY = 'Skipped: Contradictory';
    public const DUPLICATE = 'Skipped: Duplicate';
    public const EXCLUDED = 'Skipped: Excluded';
    public const INVALID_CODE = 'Skipped: Invalid code';
    public const INVALID_TEXT = 'Skipped: Invalid text';
    public const SKIPPED_LOW_FREQUENCY = 'Skipped: Low frequency';
    public const VALID_LOW_FREQUENCY = 'Valid: Low frequency';
    public const TOTAL = 'Total input rows';
    public const VALID = 'Valid';

    protected array $stats;

    public function __construct()
    {
        $this->stats = [
            self::TOTAL => 0,
            self::EXCLUDED => 0,
            self::CONTRADICTORY => 0,
            self::DUPLICATE => 0,
            self::INVALID_CODE => 0,
            self::INVALID_TEXT => 0,
            self::SKIPPED_LOW_FREQUENCY => 0,
            self::VALID_LOW_FREQUENCY => 0,
            self::VALID => 0,
        ];
    }

    public function record(string $type): void {
        $this->stats[$type]++;
    }

    public function asRows(): array {
        return array_map(null, array_keys($this->stats), array_values($this->stats));
    }
}