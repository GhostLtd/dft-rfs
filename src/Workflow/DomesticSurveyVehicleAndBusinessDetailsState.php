<?php


namespace App\Workflow;


use App\Entity\DomesticSurveyResponse;
use App\Form\Domestic\BusinessDetailsType;
use App\Form\Domestic\VehicleWeightsAndFuelType;
use App\Form\Domestic\VehicleAxleConfigurationType;
use App\Form\Domestic\VehicleBodyType;
use App\Form\Domestic\VehicleTrailerConfigurationType;

class DomesticSurveyVehicleAndBusinessDetailsState implements FormWizardInterface
{
    const STATE_BUSINESS_DETAILS = 'business-details';
    const STATE_VEHICLE_WEIGHTS_AND_FUEL = 'vehicle-weights-and-fuel';
    const STATE_VEHICLE_TRAILER_CONFIGURATION = 'vehicle-trailer-configuration';
    const STATE_VEHICLE_AXLE_CONFIGURATION = 'vehicle-axle-configuration';
    const STATE_VEHICLE_BODY = 'vehicle-body';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_BUSINESS_DETAILS => BusinessDetailsType::class,
        self::STATE_VEHICLE_WEIGHTS_AND_FUEL => VehicleWeightsAndFuelType::class,
        self::STATE_VEHICLE_TRAILER_CONFIGURATION => VehicleTrailerConfigurationType::class,
        self::STATE_VEHICLE_AXLE_CONFIGURATION => VehicleAxleConfigurationType::class,
        self::STATE_VEHICLE_BODY => VehicleBodyType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_BUSINESS_DETAILS => 'domestic_survey/vehicle_and_business_details/form-business-details.html.twig',
        self::STATE_VEHICLE_WEIGHTS_AND_FUEL => 'domestic_survey/vehicle_and_business_details/form-vehicle-weights-and-fuel.html.twig',
        self::STATE_VEHICLE_TRAILER_CONFIGURATION => 'domestic_survey/vehicle_and_business_details/form-vehicle-trailer-configuration.html.twig',
        self::STATE_VEHICLE_AXLE_CONFIGURATION => 'domestic_survey/vehicle_and_business_details/form-vehicle-axle-configuration.html.twig',
        self::STATE_VEHICLE_BODY => 'domestic_survey/vehicle_and_business_details/form-vehicle-body.html.twig',
    ];

    private $state = self::STATE_BUSINESS_DETAILS;

    /** @var DomesticSurveyResponse */
    private $subject;

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     * @return self
     */
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
        if (!get_class($subject) === DomesticSurveyResponse::class) throw new \InvalidArgumentException("Got " . get_class($subject) . ", expected " . DomesticSurveyResponse::class);
        $this->subject = $subject;
        return $this;
    }

    public function isValidJumpInState($state)
    {
        return (in_array($state, $this->getValidJumpInStates()));
    }

    protected function getValidJumpInStates()
    {
        $states = [self::STATE_BUSINESS_DETAILS];

        if ($this->subject->getBusinessNature() || $this->subject->getNumberOfEmployees() || $this->subject->getVehicle()->getOperationType())
        {
            $states[] = self::STATE_VEHICLE_WEIGHTS_AND_FUEL;

            if ($this->subject->getVehicle()->getCarryingCapacity() || $this->subject->getVehicle()->getFuelQuantity())
            {
                $states[] = self::STATE_VEHICLE_TRAILER_CONFIGURATION;
                $states[] = self::STATE_VEHICLE_AXLE_CONFIGURATION;

                if ($this->subject->getVehicle()->getAxleConfiguration())
                {
                    $states[] = self::STATE_VEHICLE_BODY;
                }
            }
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