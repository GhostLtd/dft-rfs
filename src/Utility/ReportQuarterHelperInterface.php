<?php

namespace App\Utility;

use DateTimeInterface;

interface ReportQuarterHelperInterface
{
    public static function getQuarterAndYear(DateTimeInterface $dateTime): array;
    public static function getDateRangeForYearAndQuarter(int $year, int $quarter): array;
}