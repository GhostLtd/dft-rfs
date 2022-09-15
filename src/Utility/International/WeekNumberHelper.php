<?php

namespace App\Utility\International;

use App\Utility\AbstractWeekNumberHelper;
use DateTime;

class WeekNumberHelper extends AbstractWeekNumberHelper
{
    protected const WEEK_ZERO = '1991-09-22';

    public static function getFirstDayOfWeek(bool $shortName): string
    {
        return $shortName ? 'Sun' : 'Sunday';
    }

    public static function getWeekNumber(DateTime $date): int
    {
        return intval(floor($date->diff(new DateTime(self::WEEK_ZERO))->days / 7));
    }

    public static function getDateForWeekNumber(int $weekNumber): DateTime
    {
        return (new DateTime(self::WEEK_ZERO))->modify("+{$weekNumber} weeks");
    }
}