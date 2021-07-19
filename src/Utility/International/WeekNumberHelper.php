<?php

namespace App\Utility\International;

use DateTime;

class WeekNumberHelper
{
    protected const WEEK_ONE = '1991-09-22';

    public static function getWeekNumber(DateTime $date)
    {
        return intval(floor($date->diff(new DateTime(self::WEEK_ONE))->days / 7));
    }

    public static function getDate(int $weekNumber)
    {
        return (new DateTime(self::WEEK_ONE))->modify("+{$weekNumber} weeks");
    }
}