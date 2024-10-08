<?php

namespace App\Utility;

use DateTime;
use DateTimeInterface;

/**
 * Terms:
 *   weekNumber [IRHS only] - a number uniquely representing a week (e.g. 1547)
 *   yearlyWeekNumber       - a number (1-53) representing a week in a given year
 */
abstract class AbstractWeekNumberHelper
{
    public static function getFirstDayOfWeek(bool $shortName): string {
        throw new \RuntimeException('getFirstDayOfWeek must be implemented');
    }

    public static function getFirstDayOfYear(int $year): DateTime
    {
        $dateTime = new DateTime("{$year}/1/1");
        return $dateTime->format('D') === static::getFirstDayOfWeek(true) ?
            $dateTime :
            $dateTime->modify('last '.static::getFirstDayOfWeek(false));
    }

    public static function getFirstDayOfYearForDate(DateTimeInterface $dateTime): DateTime
    {
        $thisYear = intval($dateTime->format('Y'));
        $nextYear = $thisYear + 1;

        $firstDayThisYear = self::getFirstDayOfYear($thisYear);
        $firstDayNextYear = self::getFirstDayOfYear($nextYear);

        return ($firstDayNextYear <= $dateTime) ? $firstDayNextYear : $firstDayThisYear;
    }

    /**
     * Returns a DateTime representing the start of the given year/yearlyWeekNumber (1-53)
     * Returns null if the yearlyWeekNumber is out of range for a given year
     */
    public static function getDateForYearAndWeek(int $year, int $yearlyWeekNumber): ?DateTime
    {
        $firstDayOfThisYear = self::getFirstDayOfYear($year);
        $firstDayOfNextYear = self::getFirstDayOfYear($year + 1);

        if ($yearlyWeekNumber < 1) {
            return null;
        }

        $dayNumber = ($yearlyWeekNumber - 1) * 7;
        $dateTime = (clone $firstDayOfThisYear)->modify("+{$dayNumber} days");

        if ($dateTime >= $firstDayOfNextYear) {
            return null;
        }

        return $dateTime;
    }

    /**
     * Given a date, returns the corresponding yearly week number and year
     */
    public static function getYearlyWeekNumberAndYear(DateTimeInterface $dateTime): array
    {
        $firstDay = self::getFirstDayOfYearForDate($dateTime);
        $numberOfDays = $firstDay->diff($dateTime)->days;

        $weekNumber = intval(floor($numberOfDays / 7)) + 1;
        $year = intval($firstDay->modify('+7 days')->format('Y'));

        return [$weekNumber, $year];
    }

    public static function getLastDayOfYear(int $year): DateTime
    {
        return self::getFirstDayOfYear($year + 1)->modify('-1 day');
    }
}
