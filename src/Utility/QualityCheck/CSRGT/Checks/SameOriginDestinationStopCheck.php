<?php

namespace App\Utility\QualityCheck\CSRGT\Checks;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\SurveyResponse;
use App\Utility\QualityCheck\QualityCheckInterface;
use App\Utility\QualityCheck\QualityFlag;

/**
 * Stop-level: a stop reporting the same origin and destination. Either the
 * vehicle didn't move (and the stop record is spurious) or a round trip was
 * compressed into one row with the actual midpoint missing.
 *
 * Summary days are not considered — at summary level, same origin/destination
 * is legitimate (round-trip-with-many-deliveries).
 */
class SameOriginDestinationStopCheck implements QualityCheckInterface
{
    public function run(SurveyResponse $response): array
    {
        $flags = [];
        foreach ($response->getDays() as $day) {
            if ($day->getHasMoreThanFiveStops()) {
                continue;
            }
            $stopNumbers = [];
            foreach ($day->getStops() as $stop) {
                if ($this->originEqualsDestination($stop)) {
                    $stopNumbers[] = $stop->getNumber();
                }
            }
            if ($stopNumbers !== []) {
                $flags[] = QualityFlag::forStops($day->getId(), 'same-origin-destination-stop', $stopNumbers);
            }
        }
        return $flags;
    }

    private function originEqualsDestination(DayStop $stop): bool
    {
        $origin = $stop->getOriginLocation();
        $destination = $stop->getDestinationLocation();
        return $origin !== null
            && $destination !== null
            && $origin === $destination;
    }
}
