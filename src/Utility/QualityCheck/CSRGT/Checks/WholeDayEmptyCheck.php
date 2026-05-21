<?php

namespace App\Utility\QualityCheck\CSRGT\Checks;

use App\Entity\Domestic\SurveyResponse;
use App\Utility\QualityCheck\CSRGT\DayMetrics;
use App\Utility\QualityCheck\QualityCheckInterface;
use App\Utility\QualityCheck\QualityFlag;
use App\Utility\QualityCheck\SubjectType;

/**
 * Day-level: the whole day was driven without carrying any cargo, while still
 * covering some distance. Days with zero distance are excluded — those are
 * already surfaced at stop level by ZeroDistanceStopCheck.
 */
class WholeDayEmptyCheck implements QualityCheckInterface
{
    public function run(SurveyResponse $response): array
    {
        $flags = [];
        foreach ($response->getDays() as $day) {
            $weight = DayMetrics::totalWeight($day);
            $distance = DayMetrics::totalDistanceMiles($day);

            if ($weight === 0 && $distance > 0) {
                $flags[] = new QualityFlag(
                    subjectType: SubjectType::DAY,
                    subjectId: $day->getId(),
                    reasonCode: 'whole-day-empty',
                );
            }
        }
        return $flags;
    }
}
