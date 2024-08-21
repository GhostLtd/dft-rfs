<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\Form\InternationalSurvey\Vehicle\ConfirmDatesType;
use App\Form\InternationalSurvey\Vehicle\VehicleAxleConfigurationType;
use App\Form\InternationalSurvey\Vehicle\VehicleBodyType;
use App\Form\InternationalSurvey\Vehicle\VehicleDetailsType;
use App\Form\InternationalSurvey\Vehicle\VehicleTrailerConfigurationType;
use App\Form\InternationalSurvey\Vehicle\VehicleWeightType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class VehicleState extends AbstractFormWizardState implements FormWizardStateInterface
{
    public const STATE_REQUEST_VEHICLE_DETAILS = 'vehicle-registration';
    public const STATE_REQUEST_CONFIRM_DATES = 'confirm-dates';
    public const STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION = 'trailer-configuration';
    public const STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION = 'axle-configuration';
    public const STATE_REQUEST_VEHICLE_BODY = 'vehicle-body';
    public const STATE_REQUEST_VEHICLE_WEIGHT = 'vehicle-weight';

    public const STATE_CHANGE_VEHICLE_DETAILS = 'change-vehicle-registration';
    public const STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION = 'change-trailer-configuration';
    public const STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION = 'change-axle-configuration';
    public const STATE_CHANGE_VEHICLE_BODY = 'change-vehicle-body';
    public const STATE_CHANGE_VEHICLE_WEIGHT = 'change-vehicle-weight';

    public const STATE_SUMMARY = 'summary';

    private const array FORM_MAP = [
        self::STATE_REQUEST_VEHICLE_DETAILS => VehicleDetailsType::class,
        self::STATE_REQUEST_CONFIRM_DATES => ConfirmDatesType::class,
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

    private const array TEMPLATE_MAP = [
        self::STATE_REQUEST_VEHICLE_DETAILS => 'international_survey/vehicle/form-vehicle-registration.html.twig',
        self::STATE_REQUEST_CONFIRM_DATES => 'international_survey/vehicle/form-confirm-dates.html.twig',
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

    /** @var Vehicle */
    private $subject;

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== Vehicle::class) {
            throw new InvalidArgumentException("Got " . $subject::class . ", expected " . Vehicle::class);
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