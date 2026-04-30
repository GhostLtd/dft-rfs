<?php

namespace App\Workflow\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class VehicleAndBusinessDetailsState extends AbstractFormWizardState
{
    public const string STATE_BUSINESS_DETAILS = 'business-details';
    public const string STATE_CHANGE_BUSINESS_DETAILS = 'change-business-details';
    public const string STATE_VEHICLE_WEIGHTS = 'vehicle-weights';
    public const string STATE_CHANGE_VEHICLE_WEIGHTS = 'change-vehicle-weights';
    public const string STATE_VEHICLE_TRAILER_CONFIGURATION = 'vehicle-trailer-configuration';
    public const string STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION = 'change-vehicle-trailer-configuration';
    public const string STATE_VEHICLE_AXLE_CONFIGURATION = 'vehicle-axle-configuration';
    public const string STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION = 'change-vehicle-axle-configuration';
    public const string STATE_VEHICLE_BODY = 'vehicle-body';
    public const string STATE_CHANGE_VEHICLE_BODY = 'change-vehicle-body';
    public const string STATE_END = 'end';

    private SurveyResponse $subject;

    #[\Override]
    public function getSubject(): SurveyResponse
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if (!$subject instanceof SurveyResponse) {
            throw new InvalidArgumentException('Got ' . ($subject ? $subject::class : 'null') . ', expected ' . SurveyResponse::class);
        }
        $this->subject = $subject;
        return $this;
    }
}
