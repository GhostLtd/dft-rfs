<?php

namespace App\Utility\Reports\DateSeries;

use App\Utility\Domestic\WeekNumberHelper;
use App\Utility\Reports\DateSeries\DateSeriesGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

class YearAndWeek implements DateSeriesGeneratorInterface
{
    /**
     * For weekMode meaning refer to:
     *   https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_week
     */
    public function __construct(protected int $weekMode = 5)
    {}

    #[\Override]
    public function dateToParts(\DateTimeInterface $dateTime): array
    {
        [$weekNum, $year] = WeekNumberHelper::getYearlyWeekNumberAndYear($dateTime);
        return [$year, $weekNum];
    }

    #[\Override]
    public function getPartsCount(): int
    {
        return 2;
    }

    /**
     * @return \Generator<array>
     */
    #[\Override]
    public function dateRangeGenerator(?\DateTime $minStart, ?\DateTime $maxStart): \Generator
    {
        [$firstYear, $firstWeek] = $this->dateToParts($minStart);
        [$lastYear, $lastWeek] = $this->dateToParts($maxStart);

        for ($year = $firstYear; $year <= $lastYear; $year++) {
            $loopFirstWeek = ($year === $firstYear) ?
                $firstWeek :
                1;

            $loopLastWeek = ($year === $lastYear) ?
                $lastWeek :
                53;

            for ($week = $loopFirstWeek; $week <= $loopLastWeek; $week++) {
                if ($year === $lastYear && $week === $lastWeek) {
                    // Skip the last year/week - i.e. $minDate <= RANGE < $maxDate
                    return;
                }

                yield [$year, $week];
            }
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