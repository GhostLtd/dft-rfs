<?php

namespace App\Utility\Cleanup;

use DateTimeInterface;

interface QuarterHelperInterface
{
    public function getStartOfQuarterOffset(int $deltaQuarters, ?\DateTime $currentDate=null): \DateTime;
    public function addQuartersToQuarterAndYear(int $year, int $quarter, int $deltaQuarters): array;
    public function getQuarterAndYear(DateTimeInterface $dateTime): array;
    public function getDateRangeForYearAndQuarter(int $year, int $quarter): array;
}
