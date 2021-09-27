<?php

namespace App\Utility;

use DateTime;
use DateTimeInterface;

interface WeekNumberHelperInterface extends ReportQuarterHelperInterface
{
    public static function getFirstDayOfWeek(bool $shortName): string;
    public static function getFirstDayOfYear(int $year): DateTime;
    public static function getFirstDayOfYearForDate(DateTimeInterface $dateTime): DateTime;
    public static function getDateForYearAndWeek(int $year, int $yearlyWeekNumber): ?DateTime;
    public static function getYearlyWeekNumberAndYear(DateTimeInterface $dateTime): array;
    public static function getLastDayOfYear(int $year): DateTime;
}