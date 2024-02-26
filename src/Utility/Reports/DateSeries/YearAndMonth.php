<?php

namespace App\Utility\Reports\DateSeries;

use App\Utility\Reports\DateSeries\DateSeriesGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

class YearAndMonth implements DateSeriesGeneratorInterface
{
    #[\Override]
    public function dateToParts(\DateTimeInterface $dateTime): array
    {
        return [
            intval($dateTime->format('Y')),
            intval($dateTime->format('n')),
        ];
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
        [$firstYear, $firstMonth] = $this->dateToParts($minStart);
        [$lastYear, $lastMonth] = $this->dateToParts($maxStart);

        for ($year = $firstYear; $year <= $lastYear; $year++) {
            $loopFirstMonth = ($year === $firstYear) ?
                $firstMonth :
                1;

            $loopLastMonth = ($year === $lastYear) ?
                $lastMonth :
                12;

            for ($month = $loopFirstMonth; $month <= $loopLastMonth; $month++) {
                if ($year === $lastYear && $month === $lastMonth) {
                    // Skip the last year/month - i.e. $minDate <= RANGE < $maxDate
                    return;
                }

                yield [$year, $month];
            }
        }
    }

    #[\Override]
    public function addToQueryBuilder(QueryBuilder $qb, string $field): QueryBuilder
    {
        return $qb
            ->addSelect($field)
            ->addSelect("MONTH($field) AS month")
            ->addSelect("YEAR($field) AS year");
    }
}