<?php

namespace App\ML\Config;

use App\ML\Stats;
use App\ML\ValidationException;

abstract class AbstractDefaultConfig extends AbstractConfig
{
    /**
     * @throws ValidationException
     */
    public function normalizeCode(string $code): string
    {
        $processInput = function($input): string {
            $input = ($input === '0') ? $input : ltrim($input, '0');
            return trim($input);
        };

        $parts = explode('.', strtoupper($code));
        $numParts = count($parts);
        if ($numParts === 1) {
            $code1 = $processInput($parts[0]);
            $code2 = '0';

            if (!is_numeric($code1)) {
                throw new ValidationException(Stats::INVALID_CODE);
            }
        } else if ($numParts === 2) {
            $code1 = $processInput($parts[0]);
            $code2 = $processInput($parts[1]);

            $isCodeTwoValid = is_numeric($code2) || $code2 === 'A' || $code2 === 'B';

            if (!is_numeric($code1) || !$isCodeTwoValid) {
                throw new ValidationException(Stats::INVALID_CODE);
            }
        } else {
            throw new ValidationException(Stats::INVALID_CODE);
        }

        return "{$code1}.{$code2}";
    }
}