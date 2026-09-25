<?php

namespace App\Utility;

/**
 * Converts contact telephone numbers to UK mobile numbers for Notify.
 */
final class UkMobileNumber
{
    /**
     * Returns an eleven-digit 07 number, or null for an invalid number or a non-mobile range.
     * Accepts UK national and 44 international formats, including an optional trunk zero.
     * Excludes 070 and 076 numbers, except Isle of Man mobiles starting 07624.
     */
    public static function normalise(?string $telephone): ?string
    {
        if ($telephone === null) {
            return null;
        }

        // Strip whitespace, hyphens and dots.
        $number = preg_replace('/[\s\-.]/u', '', $telephone) ?? '';
        // Drop a trunk 0 (bracketed or not) straight after an international prefix, e.g. "+44 (0)7700…" or "+44 07700…".
        $number = preg_replace('/^(\+44|0044|44)\(?0\)?(?=7)/', '$1', $number) ?? '';
        // Any other brackets left (e.g. around an area code) are just noise.
        $number = str_replace(['(', ')'], '', $number);
        // Normalise a +44/0044 prefix to the leading 0 of a UK national number.
        $number = preg_replace('/^(\+44|0044)/', '0', $number) ?? '';
        // A bare "44" prefix (no + and no leading 0) also becomes a leading 0.
        if (preg_match('/^44\d{10}$/', $number) === 1) {
            $number = '0'.substr($number, 2);
        }

        // Only a UK mobile range passes: 07[1-5,7-9]… generally, or Isle of Man 07624….
        return preg_match('/^07(?:[1-57-9]\d{8}|624\d{6})$/', $number) === 1 ? $number : null;
    }
}
