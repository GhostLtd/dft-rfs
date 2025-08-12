<?php

namespace App\Utility\Domestic\EmptyDayDetection;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\SurveyResponse;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class DetectorService
{
    public function __construct(
        #[TaggedIterator('csrgt.empty_day.detector', exclude: ['App\Handler\Three'])]
        protected iterable $detectors
    ) {}

    public function getEmptyDayCount(?SurveyResponse $surveyResponse): ?int
    {
        if (!$surveyResponse) {
            return null;
        }

        $count = 0;
        foreach ($surveyResponse->getDays() as $day) {
            $count += $this->isDayEmpty($day) ? 1 : 0;
        }
        return $count;
    }

    public function isDayEmpty(Day $day): bool
    {
        foreach ($this->detectors as $detector) {
            if ($detector->detect($day)) {
                return true;
            }
        }
        return false;
    }
}