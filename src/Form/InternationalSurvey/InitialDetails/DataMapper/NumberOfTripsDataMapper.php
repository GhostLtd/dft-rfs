<?php

namespace App\Form\InternationalSurvey\InitialDetails\DataMapper;

use App\Entity\International\SurveyResponse;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormInterface;

class NumberOfTripsDataMapper extends PropertyPathMapper
{
    /**
     * @param FormInterface[]|\Traversable $forms
     * @param SurveyResponse $data
     */
    public function mapFormsToData($forms, &$data)
    {
        parent::mapFormsToData($forms, $data);

        if ($data->getAnnualInternationalJourneyCount() > 0 && $data->getActivityStatus() !== SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE) {
            $data->setActivityStatus(SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE);
        }
    }
}