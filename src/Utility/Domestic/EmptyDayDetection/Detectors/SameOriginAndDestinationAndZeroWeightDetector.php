<?php

namespace App\Utility\Domestic\EmptyDayDetection\Detectors;

use App\Entity\Domestic\Day;
use App\Utility\Domestic\EmptyDayDetection\DetectorInterface;

class SameOriginAndDestinationAndZeroWeightDetector implements DetectorInterface
{

    public function detect(Day $day): bool
    {
        return $day->getHasMoreThanFiveStops()
            ? $this->detectSummaryDay($day)
            : $this->detectDetailedDay($day);
    }

    protected function detectSummaryDay(Day $day): bool
    {
        $summary = $day->getSummary();
        if ($summary->getOriginLocation() === $summary->getDestinationLocation()
            && $summary->getGoodsLoaded() === 0 && $summary->getGoodsUnloaded() === 0
        ) {
            return true;
        }
        return false;
    }

    protected function detectDetailedDay(Day $day): bool
    {
        $stops = $day->getStops();
        if ($stops->first()->getOriginLocation() === $stops->last()->getDestinationLocation()
            && $stops->reduce(fn($carry, $stop) => $carry + $stop->getWeightOfGoodsCarried(), 0) === 0
        ) {
            return true;
        }
        return false;
    }
}