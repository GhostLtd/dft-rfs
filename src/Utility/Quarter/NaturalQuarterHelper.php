<?php

namespace App\Utility\Quarter;

use DateTimeInterface;

class NaturalQuarterHelper extends AbstractQuarterHelper
{
    public function getQuarterAndYear(DateTimeInterface $dateTime): array
    {
        $year = (int) $dateTime->format('Y');

        try {
            foreach ($this->getQuarterBoundaries($year) as $quarter => $boundary) {
                if ($dateTime < new \DateTime($boundary)) {
                    return [$quarter, $year];
                }
            }
        } catch (\Exception) {}

        throw new \RuntimeException('Unable to convert datetime to quarter and year');
    }

    public function getDateRangeForYearAndQuarter(int $year, int $quarter): array
    {
        $quarterBoundaries = $this->getQuarterBoundaries($year);

        try {
            $upperBounds = $quarterBoundaries[$quarter] ?? null;
            if ($upperBounds) {
                return [
                    (new \DateTime($upperBounds))->modify('-3 months'),
                    (new \DateTime($upperBounds)),
                ];
            }
        } catch(\Exception) {}

        throw new \RuntimeException('Unable to convert quarter and year to datetime');
    }

    public function getQuarterBoundaries(int $year): array
    {
        return [
            1 => $year . "-04-01",
            2 => $year . "-07-01",
            3 => $year . "-10-01",
            4 => ($year + 1) . "-01-01",
        ];
    }
}
