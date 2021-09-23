<?php

namespace App\Utility\International;

use App\Utility\ReportQuarterHelperInterface;
use DateTimeInterface;

class ReportQuarterHelper implements ReportQuarterHelperInterface
{
    public static function getQuarterAndYear(DateTimeInterface $dateTime): array
    {
        $year = (int) $dateTime->format('Y');

        if ($dateTime < new \DateTime("{$year}-04-01")) {
            $quarter = 1;
        } else if ($dateTime < new \DateTime("{$year}-07-01")) {
            $quarter = 2;
        } else if ($dateTime < new \DateTime("{$year}-10-01")) {
            $quarter = 3;
        } else {
            $quarter = 4;
        }

        return [$quarter, $year];
    }
}