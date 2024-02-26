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
        $forms['numberOfEmployees']->setData($viewData->getNumberOfEmployees());
        $forms['businessNature']->setData($viewData->getBusinessNature());
        $forms['annualInternationalJourneyCount']->setData($viewData->getAnnualInternationalJourneyCount());
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof SurveyResponse) {
            throw new UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        $viewData->setActivityStatus($forms['activityStatus']->getData());

        $activityStatus = $viewData->getActivityStatus();
        if (in_array($activityStatus, [SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING, SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK])) {
            $viewData
                ->setNumberOfEmployees(null)
                ->setBusinessNature(null)
                ->setAnnualInternationalJourneyCount(0);
        } else {
            $viewData
                ->setNumberOfEmployees($forms['numberOfEmployees']->getData())
                ->setBusinessNature($forms['businessNature']->getData())
                ->setAnnualInternationalJourneyCount($forms['annualInternationalJourneyCount']->getData());
        }
    }
}
