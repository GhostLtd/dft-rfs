<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\InitialDetails\ActivityStatusType;
use App\Form\InternationalSurvey\InitialDetails\BusinessDetailsType;
use App\Form\InternationalSurvey\InitialDetails\ContactDetailsType;
use App\Form\InternationalSurvey\InitialDetails\NumberOfTripsType;
use App\Workflow\FormWizardInterface;
use InvalidArgumentException;

class InitialDetailsState implements FormWizardInterface
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

    private $state = self::STATE_INTRODUCTION;

    /** @var SurveyResponse */
    private $subject;

    public function getState()
    {
        return $this->state;
    }

    public function setState($state): self
    {
        $this->state = $state;
        return $this;
    }

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

    public function isValidJumpInState($state)
    {
        return (in_array($state, $this->getValidJumpInStates()));
    }

    protected function getValidJumpInStates()
    {
        $states = [self::STATE_INTRODUCTION, self::STATE_REQUEST_CONTACT_DETAILS];

        $isCommitted = $this->subject->getId();
        $activityStatus = $this->subject->getActivityStatus();
        $annualCount = $this->subject->getAnnualInternationalJourneyCount();

        $isActive = false;

        if ($annualCount !== null) {
            $states[] = self::STATE_REQUEST_NUMBER_OF_TRIPS;

            if ($annualCount === 0) {
                $states[] = self::STATE_REQUEST_ACTIVITY_STATUS;
                $isActive = $activityStatus === SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE;
            } else {
                $isActive = true;
            }
        }

        if ($isActive) {
            $states[] = self::STATE_REQUEST_BUSINESS_DETAILS;
        }

        if ($isCommitted) {
            $states[] = self::STATE_CHANGE_CONTACT_DETAILS;
        }

        return $states;
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
}