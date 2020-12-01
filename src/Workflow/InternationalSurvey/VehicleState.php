<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\Vehicle\VehicleAxleConfigurationType;
use App\Form\InternationalSurvey\Vehicle\VehicleBodyType;
use App\Form\InternationalSurvey\Vehicle\VehicleRegistrationType;
use App\Form\InternationalSurvey\Vehicle\VehicleTrailerConfigurationType;
use App\Workflow\FormWizardInterface;
use InvalidArgumentException;

class VehicleState implements FormWizardInterface
{
    const STATE_REQUEST_VEHICLE_REGISTRATION = 'vehicle-registration';
    const STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION = 'trailer-configuration';
    const STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION = 'axle-configuration';
    const STATE_REQUEST_VEHICLE_BODY = 'vehicle-body';
    const STATE_REQUEST_VEHICLE_WEIGHT = 'vehicle-weight';
    const STATE_REQUEST_TRAVEL_DATES = 'travel-dates';

    const STATE_SUMMARY = 'summary';

    private const FORM_MAP = [
        self::STATE_REQUEST_VEHICLE_REGISTRATION => VehicleRegistrationType::class,
        self::STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION => VehicleTrailerConfigurationType::class,
        self::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION => VehicleAxleConfigurationType::class,
        self::STATE_REQUEST_VEHICLE_BODY => VehicleBodyType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_REQUEST_VEHICLE_REGISTRATION => 'international_survey/vehicle/form-vehicle-registration.html.twig',
        self::STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION => 'international_survey/vehicle/form-trailer-configuration.html.twig',
        self::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION => 'international_survey/vehicle/form-axle-configuration.html.twig',
        self::STATE_REQUEST_VEHICLE_BODY => 'international_survey/vehicle/form-vehicle-body.html.twig',
        self::STATE_REQUEST_VEHICLE_WEIGHT => 'international_survey/vehicle/form-vehicle-weight.html.twig',
        self::STATE_REQUEST_TRAVEL_DATES => 'international_survey/vehicle/form-travel-dates.html.twig',
    ];

    private $state = self::STATE_REQUEST_VEHICLE_REGISTRATION;

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
        $states = [self::STATE_REQUEST_VEHICLE_REGISTRATION];

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