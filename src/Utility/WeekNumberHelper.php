<?php

namespace App\Utility;

use DateTime;
use DateTimeInterface;

class WeekNumberHelper
{
    public static function getWeekNumberAndYear(DateTimeInterface $dateTime): array
    {
        $firstDay = self::getFirstDayOfYearForDate($dateTime);
        $numberOfDays = $firstDay->diff($dateTime)->days;

        $weekNumber = intval(floor($numberOfDays / 7)) + 1;
        $year = intval($firstDay->modify('+7 days')->format('Y'));

        return [$weekNumber, $year];
    }

    public static function getDate(int $year, int $weekNumber): ?DateTimeInterface
    {
        $firstDayOfThisYear = self::getFirstDayOfYear($year);
        $firstDayOfNextYear = self::getFirstDayOfYear($year + 1);

        if ($weekNumber < 1) {
            return null;
        }

        $dayNumber = ($weekNumber - 1) * 7;

        $dateTime = (clone $firstDayOfThisYear)->modify("+{$dayNumber} days");

        if ($dateTime >= $firstDayOfNextYear) {
            return null;
        }

        return $dateTime;
    }

    public static function getDateForWeekNumberAndGenDate(int $weekNumber, DateTimeInterface $genDate)
    {
        $year = self::getYearForWeekNumberAndGenDate($weekNumber, $genDate);
        return self::getDate($year, $weekNumber);
    }

    public static function getYearForWeekNumberAndGenDate(int $weekNumber, DateTimeInterface $genDate)
    {
        [$genWeekNumber, $genYear] = self::getWeekNumberAndYear($genDate);

        // Example:
        //  gendate 2020-11-19 is week 47 of 2020
        //
        //  If we're asked for week 1, then that's in the past, so actually we must be generating
        //  week 1 of 2021. If we're asked for week 47, then that is for this week of 2020. If we're
        //  asked for week 48, then that's next week in 2020.

        return $weekNumber < $genWeekNumber ? $genYear + 1 : $genYear;
    }

    protected static function getFirstDayOfYearForDate(DateTimeInterface $dateTime)
    {
        $thisYear = intval($dateTime->format('Y'));
        $nextYear = $thisYear + 1;

        $firstDayThisYear = self::getFirstDayOfYear($thisYear);
        $firstDayNextYear = self::getFirstDayOfYear($nextYear);

        return ($firstDayNextYear <= $dateTime) ? $firstDayNextYear : $firstDayThisYear;
    }

    protected static function getFirstDayOfYear(int $year): DateTime
    {
        $dateTime = new DateTime("{$year}/1/1");
        return $dateTime->format('D') === 'Mon' ?
            $dateTime :
            $dateTime->modify('last monday');
    }
}