<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\Form\InternationalSurvey\Vehicle\VehicleAxleConfigurationType;
use App\Form\InternationalSurvey\Vehicle\VehicleBodyType;
use App\Form\InternationalSurvey\Vehicle\VehicleDetailsType;
use App\Form\InternationalSurvey\Vehicle\VehicleTrailerConfigurationType;
use App\Form\InternationalSurvey\Vehicle\VehicleWeightType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardInterface;
use InvalidArgumentException;

class VehicleState extends AbstractFormWizardState implements FormWizardInterface
{
    const STATE_REQUEST_VEHICLE_DETAILS = 'vehicle-registration';
    const STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION = 'trailer-configuration';
    const STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION = 'axle-configuration';
    const STATE_REQUEST_VEHICLE_BODY = 'vehicle-body';
    const STATE_REQUEST_VEHICLE_WEIGHT = 'vehicle-weight';

    const STATE_CHANGE_VEHICLE_DETAILS = 'change-vehicle-registration';
    const STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION = 'change-trailer-configuration';
    const STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION = 'change-axle-configuration';
    const STATE_CHANGE_VEHICLE_BODY = 'change-vehicle-body';
    const STATE_CHANGE_VEHICLE_WEIGHT = 'change-vehicle-weight';

    const STATE_SUMMARY = 'summary';

    private const FORM_MAP = [
        self::STATE_REQUEST_VEHICLE_DETAILS => VehicleDetailsType::class,
        self::STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION => VehicleTrailerConfigurationType::class,
        self::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION => VehicleAxleConfigurationType::class,
        self::STATE_REQUEST_VEHICLE_BODY => VehicleBodyType::class,
        self::STATE_REQUEST_VEHICLE_WEIGHT => VehicleWeightType::class,

        self::STATE_CHANGE_VEHICLE_DETAILS => VehicleDetailsType::class,
        self::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION => VehicleTrailerConfigurationType::class,
        self::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION => VehicleAxleConfigurationType::class,
        self::STATE_CHANGE_VEHICLE_BODY => VehicleBodyType::class,
        self::STATE_CHANGE_VEHICLE_WEIGHT => VehicleWeightType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_REQUEST_VEHICLE_DETAILS => 'international_survey/vehicle/form-vehicle-registration.html.twig',
        self::STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION => 'international_survey/vehicle/form-trailer-configuration.html.twig',
        self::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION => 'international_survey/vehicle/form-axle-configuration.html.twig',
        self::STATE_REQUEST_VEHICLE_BODY => 'international_survey/vehicle/form-vehicle-body.html.twig',
        self::STATE_REQUEST_VEHICLE_WEIGHT => 'international_survey/vehicle/form-vehicle-weight.html.twig',

        self::STATE_CHANGE_VEHICLE_DETAILS => 'international_survey/vehicle/form-vehicle-registration.html.twig',
        self::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION => 'international_survey/vehicle/form-trailer-configuration.html.twig',
        self::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION => 'international_survey/vehicle/form-axle-configuration.html.twig',
        self::STATE_CHANGE_VEHICLE_BODY => 'international_survey/vehicle/form-vehicle-body.html.twig',
        self::STATE_CHANGE_VEHICLE_WEIGHT => 'international_survey/vehicle/form-vehicle-weight.html.twig',
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
        if (!$this->subject instanceof Vehicle) {
            return false;
        }

        $alternativeStartStates = [
            self::STATE_CHANGE_VEHICLE_DETAILS,
            self::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION,
            self::STATE_CHANGE_VEHICLE_BODY,
            self::STATE_CHANGE_VEHICLE_WEIGHT,
        ];

        return $this->subject->getId() ?
            in_array($state, $alternativeStartStates) :
            false;
    }
}