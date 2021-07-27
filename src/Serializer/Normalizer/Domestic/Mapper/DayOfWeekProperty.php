<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\StopTrait;
use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DayOfWeekProperty implements Mapper
{
    private string $datePropertyPath;
    private string $dayNumberProperty;

    public function getData($sourceData)
    {
        /** @var DaySummary|DayStop $sourceData */

        try {
            /** @var \DateTime $startDate */
            $startDate = clone $sourceData->getDay()->getResponse()->getSurvey()->getSurveyPeriodStart();
            $days = $sourceData->getDay()->getNumber() - 1;
            $startDate->modify("+{$days} days");
            return $startDate->format('D');
        } catch (\Throwable $e) {
            return null;
        }
    }
}