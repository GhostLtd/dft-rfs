<?php

namespace App\Utility\Form;

class UnitStripper
{
    /** @param string[] $units */
    public function strip(?string $value, array $units): ?string
    {
        if ($value === null || $units === []) {
            return $value;
        }

        $value = trim($value);

        // Longest first so e.g. "kg" is matched before a hypothetical "g".
        usort($units, fn(string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($units as $unit) {
            if ($unit === '') {
                continue;
            }
            $pattern = '/\s*' . preg_quote($unit, '/') . '$/i';
            if (preg_match($pattern, $value)) {
                return trim((string) preg_replace($pattern, '', $value));
            }
        }

        return $value;
    }
}
