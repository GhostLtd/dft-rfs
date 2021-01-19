<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\InitialDetails\ActivityStatusType;
use App\Form\InternationalSurvey\InitialDetails\BusinessDetailsType;
use App\Form\InternationalSurvey\InitialDetails\ContactDetailsType;
use App\Form\InternationalSurvey\InitialDetails\NumberOfTripsType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class InitialDetailsState extends AbstractFormWizardState implements FormWizardStateInterface
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_REQUEST_CONTACT_DETAILS = 'contact-details';
    const STATE_REQUEST_NUMBER_OF_TRIPS = 'number-of-trips';
    const STATE_REQUEST_ACTIVITY_STATUS = 'activity-status';
    const STATE_REQUEST_BUSINESS_DETAILS = 'business-details';

    const STATE_CHANGE_CONTACT_DETAILS = 'change-contact-details';

    const STATE_SUMMARY = 'summary';

    private const FORM_MAP = [
        self::STATE_REQUEST_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_REQUEST_NUMBER_OF_TRIPS => NumberOfTripsType::class,
        self::STATE_REQUEST_ACTIVITY_STATUS => ActivityStatusType::class,
        self::STATE_REQUEST_BUSINESS_DETAILS => BusinessDetailsType::class,

        self::STATE_CHANGE_CONTACT_DETAILS => ContactDetailsType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_INTRODUCTION => 'international_survey/initial_details/introduction.html.twig',
        self::STATE_REQUEST_CONTACT_DETAILS => 'international_survey/initial_details/form-contact-details.html.twig',
        self::STATE_REQUEST_NUMBER_OF_TRIPS => 'international_survey/initial_details/form-number-of-trips.html.twig',
        self::STATE_REQUEST_ACTIVITY_STATUS => 'international_survey/initial_details/form-activity-status.html.twig',
        self::STATE_REQUEST_BUSINESS_DETAILS => 'international_survey/initial_details/form-business-details.html.twig',

        self::STATE_CHANGE_CONTACT_DETAILS => 'international_survey/initial_details/form-contact-details.html.twig',
    ];

    /** @var SurveyResponse */
    private $subject;

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === SurveyResponse::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . SurveyResponse::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function getStateFormMap()
    {
        return self::FORM_MAP;
    }

    public function getStateTemplateMap()
    {
        return self::TEMPLATE_MAP;
    }

    public function getDefaultTemplate()
    {
        return null;
    }

    public function isValidAlternativeStartState($state): bool
    {
        $response = ($this->subject && $this->subject instanceof SurveyResponse) ? $this->subject : null;
        $isCommitted = $response && !!$response->getId();

        if ($isCommitted) {
            $validStates = [
                InitialDetailsState::STATE_CHANGE_CONTACT_DETAILS,
                InitialDetailsState::STATE_REQUEST_NUMBER_OF_TRIPS,
                InitialDetailsState::STATE_REQUEST_BUSINESS_DETAILS,
            ];

            if ($response->isNoLongerActive()) {
                $validStates[] = InitialDetailsState::STATE_REQUEST_ACTIVITY_STATUS;
            }

            return in_array($state, $validStates);
        }

        return false;
    }
}