<?php

namespace App\Utility\QualityCheck\CSRGT;

use App\Entity\Domestic\Day;
use App\Entity\Embeddable\Distance;

/**
 * Helpers for summing weight and distance across the two Day representations
 * (detailed = individual stops, summary = aggregated for >5 stops).
 */
final class DayMetrics
{
    private function __construct() {}

    public static function totalWeight(Day $day): int
    {
        if ($day->getHasMoreThanFiveStops()) {
            $summary = $day->getSummary();
            return $summary === null
                ? 0
                : ($summary->getWeightOfGoodsLoaded() ?? 0) + ($summary->getWeightOfGoodsUnloaded() ?? 0);
        }

        return (int) $day->getStops()->reduce(
            fn($carry, $stop) => $carry + ($stop->getWeightOfGoodsCarried() ?? 0),
            0,
        );
    }

    public static function totalDistanceMiles(Day $day): float
    {
        if ($day->getHasMoreThanFiveStops()) {
            $summary = $day->getSummary();
            if ($summary === null) {
                return 0.0;
            }
            return self::distanceToMiles($summary->getDistanceTravelledLoaded())
                + self::distanceToMiles($summary->getDistanceTravelledUnloaded());
        }

        return (float) $day->getStops()->reduce(
            fn($carry, $stop) => $carry + self::distanceToMiles($stop->getDistanceTravelled()),
            0.0,
        );
    }

    private static function distanceToMiles(?Distance $distance): float
    {
        return (float) $distance?->getValueNormalized(Distance::UNIT_MILES);
    }
}
