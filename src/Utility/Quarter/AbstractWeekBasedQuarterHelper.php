<?php

namespace App\Utility\Quarter;

use App\Utility\AbstractWeekNumberHelper;
use DateTimeInterface;

abstract class AbstractWeekBasedQuarterHelper extends AbstractQuarterHelper
{
    protected const array QUARTERS_TO_WEEK = [
        1 => 1,
        2 => 14,
        3 => 27,
        4 => 40,
    ];

    protected AbstractWeekNumberHelper $weekNumberHelper;

    public function getQuarterAndYear(DateTimeInterface $dateTime): array
    {
        [$week, $year] = $this->weekNumberHelper->getYearlyWeekNumberAndYear($dateTime);
        foreach (array_reverse(array_keys(self::QUARTERS_TO_WEEK)) as $quarter) {
            $quarterStartWeek = self::QUARTERS_TO_WEEK[$quarter];
            if ($quarterStartWeek <= $week) {
                return [$quarter, $year];
            }
        }
        throw new \InvalidArgumentException();
    }

    public function getDateRangeForYearAndQuarter(int $year, int $quarter): array
    {
        $startDate = $this->weekNumberHelper->getDateForYearAndWeek($year, self::QUARTERS_TO_WEEK[$quarter]);

        if ($quarter === 4) {
            $nextDate = $this->weekNumberHelper->getDateForYearAndWeek($year + 1, self::QUARTERS_TO_WEEK[1]);
        } else {
            $nextDate = $this->weekNumberHelper->getDateForYearAndWeek($year, self::QUARTERS_TO_WEEK[$quarter + 1]);
        }

        return [$startDate, $nextDate];
    }
}
