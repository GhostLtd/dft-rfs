<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Serializer\Normalizer\Mapper\Mapper;

class DayOfWeekProperty implements Mapper
{
    private string $datePropertyPath;
    private string $dayNumberProperty;

    #[\Override]
    public function getData($sourceData): ?string
    {
        /** @var DaySummary|DayStop $sourceData */

        try {
            /** @var \DateTime $startDate */
            $startDate = clone $sourceData->getDay()->getResponse()->getSurvey()->getSurveyPeriodStart();
            $days = $sourceData->getDay()->getNumber() - 1;
            $startDate->modify("+{$days} days");
            return $startDate->format('D');
        } catch (\Throwable) {
            return null;
        }
    }
}