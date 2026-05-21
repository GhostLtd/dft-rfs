<?php

namespace App\Utility\QualityCheck\CSRGT\Checks;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\SurveyResponse;
use App\Utility\QualityCheck\QualityCheckInterface;
use App\Utility\QualityCheck\QualityFlag;

/**
 * Stop-level: a stop reporting zero distance. A "stop" should represent a
 * leg from origin to destination; zero distance either means no movement
 * occurred (in which case the record probably shouldn't exist) or distance
 * was forgotten (data entry error). Either way it warrants review.
 *
 * Summary days are not considered — they don't expose individual stops.
 */
class ZeroDistanceStopCheck implements QualityCheckInterface
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
                if ($this->hasZeroDistance($stop)) {
                    $stopNumbers[] = $stop->getNumber();
                }
            }
            if ($stopNumbers !== []) {
                $flags[] = QualityFlag::forStops($day->getId(), 'zero-distance-stop', $stopNumbers);
            }
        }
        return $flags;
    }

    private function hasZeroDistance(DayStop $stop): bool
    {
        return ((float) $stop->getDistanceTravelled()?->getValue()) === 0.0;
    }
}
