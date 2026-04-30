<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class InitialDetailsState extends AbstractFormWizardState
{
    public const string STATE_INTRODUCTION = 'introduction';
    public const string STATE_REQUEST_CONTACT_DETAILS = 'contact-details';
    public const string STATE_REQUEST_NUMBER_OF_TRIPS = 'number-of-trips';
    public const string STATE_REQUEST_ACTIVITY_STATUS = 'activity-status';
    public const string STATE_REQUEST_BUSINESS_DETAILS = 'business-details';
    public const string STATE_CHANGE_CONTACT_DETAILS = 'change-contact-details';
    public const string STATE_SUMMARY = 'summary';

    private SurveyResponse $subject;

    public function getSubject(): ?SurveyResponse
    {
        return $this->subject ?? null;
    }

    public function setSubject($subject): self
    {
        if (!$subject instanceof SurveyResponse) {
            throw new InvalidArgumentException('Got ' . $subject::class . ', expected ' . SurveyResponse::class);
        }
        $this->subject = $subject;
        return $this;
    }
}
