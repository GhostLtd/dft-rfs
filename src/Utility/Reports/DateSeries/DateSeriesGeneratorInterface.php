<?php

namespace App\Utility\Reports\DateSeries;

use Doctrine\ORM\QueryBuilder;

interface DateSeriesGeneratorInterface
{
    public function dateToParts(\DateTimeInterface $dateTime): array;

    /**
     * @return \Generator<array>
     */
    public function dateRangeGenerator(?\DateTime $minStart, ?\DateTime $maxStart): \Generator;

    public function addToQueryBuilder(QueryBuilder $qb, string $field): QueryBuilder;

    public function getPartsCount(): int;
}