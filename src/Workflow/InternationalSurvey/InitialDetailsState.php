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
    public const STATE_INTRODUCTION = 'introduction';
    public const STATE_REQUEST_CONTACT_DETAILS = 'contact-details';
    public const STATE_REQUEST_NUMBER_OF_TRIPS = 'number-of-trips';
    public const STATE_REQUEST_ACTIVITY_STATUS = 'activity-status';
    public const STATE_REQUEST_BUSINESS_DETAILS = 'business-details';

    public const STATE_CHANGE_CONTACT_DETAILS = 'change-contact-details';

    public const STATE_SUMMARY = 'summary';

    private const array FORM_MAP = [
        self::STATE_REQUEST_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_REQUEST_NUMBER_OF_TRIPS => NumberOfTripsType::class,
        self::STATE_REQUEST_ACTIVITY_STATUS => ActivityStatusType::class,
        self::STATE_REQUEST_BUSINESS_DETAILS => BusinessDetailsType::class,

        self::STATE_CHANGE_CONTACT_DETAILS => ContactDetailsType::class,
    ];

    private const array TEMPLATE_MAP = [
        self::STATE_INTRODUCTION => 'international_survey/initial_details/introduction.html.twig',
        self::STATE_REQUEST_CONTACT_DETAILS => 'international_survey/initial_details/form-contact-details.html.twig',
        self::STATE_REQUEST_NUMBER_OF_TRIPS => 'international_survey/initial_details/form-number-of-trips.html.twig',
        self::STATE_REQUEST_ACTIVITY_STATUS => 'international_survey/initial_details/form-activity-status.html.twig',
        self::STATE_REQUEST_BUSINESS_DETAILS => 'international_survey/initial_details/form-business-details.html.twig',

        self::STATE_CHANGE_CONTACT_DETAILS => 'international_survey/initial_details/form-contact-details.html.twig',
    ];

    /** @var SurveyResponse */
    private $subject;

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== SurveyResponse::class) {
            throw new InvalidArgumentException("Got " . $subject::class . ", expected " . SurveyResponse::class);
        }
        $this->subject = $subject;
        return $this;
    }

    #[\Override]
    public function getStateFormMap()
    {
        return self::FORM_MAP;
    }

    #[\Override]
    public function getStateTemplateMap()
    {
        return self::TEMPLATE_MAP;
    }

    #[\Override]
    public function getDefaultTemplate()
    {
        return null;
    }

    #[\Override]
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