<?php

namespace App\Form\InternationalSurvey\InitialDetails\DataMapper;

use App\Entity\International\SurveyResponse;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

class NumberOfTripsDataMapper extends DataMapper
{
    #[\Override]
    public function mapFormsToData(\Traversable $forms, &$data): void
    {
        parent::mapFormsToData($forms, $data);

        if ($data->getAnnualInternationalJourneyCount() > 0 && $data->getActivityStatus() !== SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE) {
            $data->setActivityStatus(SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE);
        }
    }
}
