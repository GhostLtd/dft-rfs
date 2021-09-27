<?php

namespace App\Utility\International;

use App\Utility\ReportQuarterHelperInterface;
use DateTimeInterface;

class ReportQuarterHelper implements ReportQuarterHelperInterface
{
    public static function getQuarterBoundaries(int $year): array
    {
        return [
            1 => $year . "-04-01",
            2 => $year . "-07-01",
            3 => $year . "-10-01",
            4 => ($year + 1) . "-01-01",
        ];
    }

    public static function getQuarterAndYear(DateTimeInterface $dateTime): array
    {
        $year = (int)$dateTime->format('Y');

        try {
            foreach (self::getQuarterBoundaries($year) as $quarter => $boundary) {
                if ($dateTime < new \DateTime($boundary)) {
                    return [$quarter, $year];
                }
            }
        } catch (\Exception $e) {}
        throw new \RuntimeException('Unable to convert datetime to quarter and year');
    }

    public static function getDateRangeForYearAndQuarter(int $year, int $quarter): array
    {
        $quarterBoundaries = self::getQuarterBoundaries($year);

        try {
            $upperBounds = $quarterBoundaries[$quarter] ?? null;
            if ($upperBounds) {
                return [
                    (new \DateTime($upperBounds))->modify('-3 months'),
                    (new \DateTime($upperBounds)),
                ];
            }
        } catch(\Exception $e) {}

        throw new \RuntimeException('Unable to convert quarter and year to datetime');
    }
}