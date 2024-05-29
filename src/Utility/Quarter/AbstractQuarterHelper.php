<?php

namespace App\Utility\Quarter;

use App\Utility\Cleanup\QuarterHelperInterface;
use DateTimeInterface;

abstract class AbstractQuarterHelper implements QuarterHelperInterface
{
    public function getStartOfQuarterOffset(int $deltaQuarters, ?\DateTime $currentDate=null): \DateTime
    {
        $currentDate ??= new \DateTime();

        [$year, $quarter] = $this->getQuarterAndYear($currentDate);
        [$targetYear, $targetQuarter] = $this->addQuartersToQuarterAndYear($quarter, $year, $deltaQuarters);

        [$targetQuarterStart, $_targetQuarterEnd] = $this->getDateRangeForYearAndQuarter($targetYear, $targetQuarter);

        return $targetQuarterStart;
    }

    public function addQuartersToQuarterAndYear(int $year, int $quarter, int $deltaQuarters): array
    {
        $effectiveYear = $year + (($quarter - 1) * 0.25) + ($deltaQuarters * 0.25);

        return [
            intval(floor($effectiveYear)),
            intval(($effectiveYear - floor($effectiveYear)) / 0.25) + 1
        ];
    }

    /**
     * Given a date, returns the corresponding quarter and year
     */
    abstract public function getQuarterAndYear(DateTimeInterface $dateTime): array;

    /**
     * Returns an array comprising two dates - the start of the quarter and the start of the next quarter
     */
    abstract public function getDateRangeForYearAndQuarter(int $year, int $quarter): array;
}
