<?php

namespace App\Utility\Domestic;

use App\Utility\AbstractWeekNumberHelper;
use DateTime;
use DateTimeInterface;

class WeekNumberHelper extends AbstractWeekNumberHelper
{
    public static function getFirstDayOfWeek(bool $shortName): string
    {
        return $shortName ? 'Mon' : 'Monday';
    }

    public static function getDateForWeekNumberAndGenDate(int $weekNumber, DateTimeInterface $genDate): ?DateTime
    {
        $year = self::getYearForWeekNumberAndGenDate($weekNumber, $genDate);
        return self::getDateForYearAndWeek($year, $weekNumber);
    }

    public static function getYearForWeekNumberAndGenDate(int $weekNumber, DateTimeInterface $genDate): int
    {
        [$genWeekNumber, $genYear] = self::getYearlyWeekNumberAndYear($genDate);

        // Example:
        //  gendate 2020-11-19 is week 47 of 2020
        //
        //  If we're asked for week 1, then that's in the past, so actually we must be generating
        //  week 1 of 2021. If we're asked for week 47, then that is for this week of 2020. If we're
        //  asked for week 48, then that's next week in 2020.

        return $weekNumber < $genWeekNumber ? $genYear + 1 : $genYear;
    }
}