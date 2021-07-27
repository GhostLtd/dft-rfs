<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\BusinessDetailsType;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleWeightsType;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleAxleConfigurationType;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleBodyType;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleTrailerConfigurationType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;

class VehicleAndBusinessDetailsState extends AbstractFormWizardState implements FormWizardStateInterface
{
    const STATE_BUSINESS_DETAILS = 'business-details';
    const STATE_CHANGE_BUSINESS_DETAILS = 'change-business-details';
    const STATE_VEHICLE_WEIGHTS = 'vehicle-weights';
    const STATE_CHANGE_VEHICLE_WEIGHTS = 'change-vehicle-weights';
    const STATE_VEHICLE_TRAILER_CONFIGURATION = 'vehicle-trailer-configuration';
    const STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION = 'change-vehicle-trailer-configuration';
    const STATE_VEHICLE_AXLE_CONFIGURATION = 'vehicle-axle-configuration';
    const STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION = 'change-vehicle-axle-configuration';
    const STATE_VEHICLE_BODY = 'vehicle-body';
    const STATE_CHANGE_VEHICLE_BODY = 'change-vehicle-body';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_BUSINESS_DETAILS => BusinessDetailsType::class,
        self::STATE_CHANGE_BUSINESS_DETAILS => BusinessDetailsType::class,
        self::STATE_VEHICLE_WEIGHTS => VehicleWeightsType::class,
        self::STATE_CHANGE_VEHICLE_WEIGHTS => VehicleWeightsType::class,
        self::STATE_VEHICLE_TRAILER_CONFIGURATION => VehicleTrailerConfigurationType::class,
        self::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION => VehicleTrailerConfigurationType::class,
        self::STATE_VEHICLE_AXLE_CONFIGURATION => VehicleAxleConfigurationType::class,
        self::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION => VehicleAxleConfigurationType::class,
        self::STATE_VEHICLE_BODY => VehicleBodyType::class,
        self::STATE_CHANGE_VEHICLE_BODY => VehicleBodyType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_BUSINESS_DETAILS => 'domestic_survey/vehicle_and_business_details/form-business-details.html.twig',
        self::STATE_CHANGE_BUSINESS_DETAILS => 'domestic_survey/vehicle_and_business_details/form-business-details.html.twig',
        self::STATE_VEHICLE_WEIGHTS => 'domestic_survey/vehicle_and_business_details/form-vehicle-weights.html.twig',
        self::STATE_CHANGE_VEHICLE_WEIGHTS => 'domestic_survey/vehicle_and_business_details/form-vehicle-weights.html.twig',
        self::STATE_VEHICLE_TRAILER_CONFIGURATION => 'domestic_survey/vehicle_and_business_details/form-vehicle-trailer-configuration.html.twig',
        self::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION => 'domestic_survey/vehicle_and_business_details/form-vehicle-trailer-configuration.html.twig',
        self::STATE_VEHICLE_AXLE_CONFIGURATION => 'domestic_survey/vehicle_and_business_details/form-vehicle-axle-configuration.html.twig',
        self::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION => 'domestic_survey/vehicle_and_business_details/form-vehicle-axle-configuration.html.twig',
        self::STATE_VEHICLE_BODY => 'domestic_survey/vehicle_and_business_details/form-vehicle-body.html.twig',
        self::STATE_CHANGE_VEHICLE_BODY => 'domestic_survey/vehicle_and_business_details/form-vehicle-body.html.twig',
    ];

    /** @var SurveyResponse */
    private $subject;

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === SurveyResponse::class) throw new \InvalidArgumentException("Got " . get_class($subject) . ", expected " . SurveyResponse::class);
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
        if ($this->subject->getId()) {
            return in_array($state, [
                self::STATE_CHANGE_VEHICLE_WEIGHTS,
                self::STATE_CHANGE_BUSINESS_DETAILS,
                self::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION,
            ]);
        }
        return parent::isValidAlternativeStartState($state);
    }
}