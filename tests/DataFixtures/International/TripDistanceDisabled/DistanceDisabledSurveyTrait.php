<?php

namespace App\Tests\DataFixtures\International\TripDistanceDisabled;

use App\Entity\International\Survey;
use Doctrine\Persistence\ObjectManager;

trait DistanceDisabledSurveyTrait
{
    protected function updateSurveyDates(ObjectManager $manager, Survey $survey): void
    {
        $date = new \DateTime(Survey::DISABLE_TRIP_DISTANCE_FROM_SURVEY_PERIOD_START_DATE . ' midnight');
        $survey->setSurveyPeriodStart(clone ($date->modify('+1 month')));
        $survey->setSurveyPeriodEnd(clone ($date->modify('+10 days')));

        $manager->flush();
    }
}