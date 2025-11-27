<?php

namespace App\Form\Admin\InternationalSurvey\DataMapper;

use App\Entity\International\SurveyResponse;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

class BusinessDetailsDataMapper implements DataMapperInterface
{
    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof SurveyResponse) {
            throw new UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        // initialize form field values
        $forms['activityStatus']->setData($viewData->getActivityStatus());
        $forms['activityStatusOther']->setData($viewData->getActivityStatusOther());
        $forms['numberOfEmployees']->setData($viewData->getNumberOfEmployees());
        $forms['businessNature']->setData($viewData->getBusinessNature());
        $forms['annualInternationalHGVJourneyCount']->setData($viewData->getAnnualInternationalHGVJourneyCount());
        $forms['annualInternationalLGVJourneyCount']->setData($viewData->getAnnualInternationalLGVJourneyCount());
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof SurveyResponse) {
            throw new UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        $viewData
            ->setActivityStatus($forms['activityStatus']->getData())
            ->setNumberOfEmployees($forms['numberOfEmployees']->getData())
            ->setBusinessNature($forms['businessNature']->getData());

        $activityStatusOther = $viewData->getActivityStatus() === SurveyResponse::ACTIVITY_STATUS_NO_ROAD_HAULAGE
            ? $forms['activityStatusOther']->getData()
            : null;

        $viewData->setActivityStatusOther($activityStatusOther);

        $hgvJourneyCount = $viewData->isNoLongerActive()
            ? null
            : $forms['annualInternationalHGVJourneyCount']->getData();

        $viewData->setAnnualInternationalHGVJourneyCount($hgvJourneyCount);

        $lgvJourneyCount = $viewData->isNoLongerActive()
            ? null
            : $forms['annualInternationalLGVJourneyCount']->getData();

        $viewData->setAnnualInternationalLGVJourneyCount($lgvJourneyCount);
    }
}
