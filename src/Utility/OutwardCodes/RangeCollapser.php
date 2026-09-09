<?php

namespace App\Utility\OutwardCodes;

/**
 * Collapses a set of outward codes (postcode districts, e.g. "BN1") into a compact
 * per-area list of tokens, where runs of consecutive numeric districts become ranges:
 *
 *   BN1, BN2, BN3, BN4, BN7, BN8, BN9  =>  BN1-4, BN7-9
 *
 * Letter-suffixed districts (e.g. SW1A, E1W) are never merged into a range and are
 * emitted individually, but they do not break a numeric run either:
 *
 *   E1, E1W, E2, E3  =>  E1-3, E1W
 */
final class RangeCollapser
{
    private const string NUMERIC_RE = '/^([A-Z]{1,2})(\d{1,2})$/';
    private const string SUFFIXED_RE = '/^([A-Z]{1,2})(\d{1,2})([A-Z])$/';
    private const string AREA_RE = '/^[A-Z]+/';

    /**
     * @param iterable<string> $outwardCodes
     * @return array<string, list<string>> postcode area => tokens, areas in alphabetical order
     */
    public static function collapse(iterable $outwardCodes): array
    {
        /** @var array<string, array{numeric: array<int, true>, suffixed: array<string, array{int, string}>, other: array<string, true>}> $areas */
        $areas = [];

        foreach ($outwardCodes as $code) {
            $code = strtoupper(trim($code));
            if ($code === '') {
                continue;
            }

            $area = preg_match(self::AREA_RE, $code, $m) ? $m[0] : $code;
            $areas[$area] ??= ['numeric' => [], 'suffixed' => [], 'other' => []];

            if (preg_match(self::NUMERIC_RE, $code, $m)) {
                $areas[$area]['numeric'][(int) $m[2]] = true;
            } elseif (preg_match(self::SUFFIXED_RE, $code, $m)) {
                $areas[$area]['suffixed'][$code] = [(int) $m[2], $m[3]];
            } else {
                $areas[$area]['other'][$code] = true;
            }
        }

        ksort($areas, SORT_STRING);

        $result = [];
        foreach ($areas as $area => $groups) {
            /** @var list<array{int, string, string}> $tokens [number, suffix, token] */
            $tokens = [];

            $numbers = array_keys($groups['numeric']);
            sort($numbers, SORT_NUMERIC);
            foreach (self::runs($numbers) as [$start, $end]) {
                $tokens[] = [$start, '', $start === $end ? "{$area}{$start}" : "{$area}{$start}-{$end}"];
            }

            foreach ($groups['suffixed'] as $code => [$number, $suffix]) {
                $tokens[] = [$number, $suffix, $code];
            }

            usort($tokens, fn(array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

            $others = array_keys($groups['other']);
            sort($others, SORT_STRING);

            $result[$area] = [...array_column($tokens, 2), ...$others];
        }

        return $result;
    }

    /**
     * @param array<string, list<string>> $collapsed as returned by collapse()
     */
    public static function format(array $collapsed): string
    {
        $lines = array_map(fn(array $tokens): string => implode(',', $tokens), $collapsed);

        return implode("\n", $lines) . ($lines ? "\n" : '');
    }

    /**
     * @param list<int> $sortedNumbers ascending, unique
     * @return list<array{int, int}> [start, end] pairs of consecutive runs
     */
    private static function runs(array $sortedNumbers): array
    {
        $runs = [];
        $start = $prev = null;

        foreach ($sortedNumbers as $n) {
            if ($start === null) {
                $start = $prev = $n;
                continue;
            }

            if ($n === $prev + 1) {
                $prev = $n;
                continue;
            }

            $runs[] = [$start, $prev];
            $start = $prev = $n;
        }

        if ($start !== null) {
            $runs[] = [$start, $prev];
        }

        return $runs;
    }
}
