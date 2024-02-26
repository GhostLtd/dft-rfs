<?php

namespace App\Utility\Reports\DateSeries;

use App\Utility\International\WeekNumberHelper;
use App\Utility\Reports\DateSeries\DateSeriesGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

class InternationalWeek implements DateSeriesGeneratorInterface
{
    /**
     * For weekMode meaning refer to:
     *   https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_week
     */
    public function __construct(protected int $weekMode = 0)
    {}

    #[\Override]
    public function dateToParts(\DateTimeInterface $dateTime): array
    {
        $weekNum = WeekNumberHelper::getWeekNumber($dateTime);
        return [$weekNum];
    }

    #[\Override]
    public function getPartsCount(): int
    {
        return 1;
    }

    /**
     * @return \Generator<array>
     */
    #[\Override]
    public function dateRangeGenerator(?\DateTime $minStart, ?\DateTime $maxStart): \Generator
    {
        [$firstWeek] = $this->dateToParts($minStart);
        [$lastWeek] = $this->dateToParts($maxStart);

        for ($week = $firstWeek; $week < $lastWeek; $week++) {
            yield [$week];
        }
    }

    #[\Override]
    public function addToQueryBuilder(QueryBuilder $qb, string $field): QueryBuilder
    {
        return $qb
            ->addSelect($field)
            ->addSelect("WEEK({$field}, {$this->weekMode}) AS week")
            ->addSelect("YEAR({$field}) AS year");
    }
}