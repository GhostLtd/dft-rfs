<?php

namespace App\Utility\Form;

/**
 * Converts an explicitly tonnes-denominated weight to kilograms: e.g. "3.8 T" -> "3800",
 * "3,5 TON" -> "3500". A single comma or dot is treated as the decimal point. Only acts when:
 *  - a tonnes unit (t/ton/tonne/tons/tonnes) is present (never converts a bare number),
 *  - any fractional part is 1–2 digits (3+ digits could be a thousands group, e.g. "3,500" - too
 *    ambiguous to guess, so left for validation), and
 *  - the tonnes value is below MAX_TONNES (a sanity bound — larger values are left for validation).
 * Otherwise the value is returned unchanged.
 */
class TonnesToKilogramsNormaliser
{
    /** Longest first so the regex alternation prefers e.g. "ton"/"tons" over "t". */
    private const array UNITS = ['tonnes', 'tonne', 'tons', 'ton', 't'];
    private const int MAX_TONNES = 100;

    public function normalise(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        $units = implode('|', array_map(
            fn(string $unit) => preg_quote($unit, '/'),
            self::UNITS,
        ));
        $pattern = '/^(?<number>\d+(?:[.,]\d{1,2})?)\s*(?:' . $units . ')$/i';

        if (preg_match($pattern, $value, $matches)) {
            $tonnes = (float) str_replace(',', '.', $matches['number']);
            if ($tonnes < self::MAX_TONNES) {
                return (string) (int) round($tonnes * 1000);
            }
        }

        return $value;
    }
}
