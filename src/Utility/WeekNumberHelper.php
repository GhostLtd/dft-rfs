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