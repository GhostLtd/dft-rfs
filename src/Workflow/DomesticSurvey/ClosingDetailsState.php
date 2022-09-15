<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\ClosingDetails\MissingDaysType;
use App\Form\DomesticSurvey\ClosingDetails\ReasonEmptySurveyType;
use App\Form\DomesticSurvey\ClosingDetails\VehicleFuelType;
use App\Form\DomesticSurvey\DriverAvailability\BonusesType;
use App\Form\DomesticSurvey\DriverAvailability\DeliveriesType;
use App\Form\DomesticSurvey\DriverAvailability\DriversAndVacanciesType;
use App\Form\DomesticSurvey\DriverAvailability\WagesType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;

class ClosingDetailsState extends AbstractFormWizardState implements FormWizardStateInterface
{
    const STATE_START = 'start';
    const STATE_VEHICLE_FUEL = 'vehicle-fuel';
    const STATE_MISSING_DAYS = 'missing-days';
    const STATE_REASON_EMPTY_SURVEY = 'empty-survey';

    const STATE_DRIVER_AVAILABILITY_DRIVERS = 'da-drivers';
    const STATE_DRIVER_AVAILABILITY_WAGES = 'da-wages';
    const STATE_DRIVER_AVAILABILITY_BONUSES = 'da-bonuses';
    const STATE_DRIVER_AVAILABILITY_DELIVERIES = 'da-deliveries';

    const STATE_CONFIRM = 'confirm';
    const STATE_END = 'end';

    const IN_POSSESSION_OF_VEHICLE_JUMP_IN_STATE = self::STATE_START;

    private const FORM_MAP = [
        self::STATE_MISSING_DAYS => MissingDaysType::class,
        self::STATE_VEHICLE_FUEL => VehicleFuelType::class,
        self::STATE_REASON_EMPTY_SURVEY => ReasonEmptySurveyType::class,

        self::STATE_DRIVER_AVAILABILITY_DRIVERS => DriversAndVacanciesType::class,
        self::STATE_DRIVER_AVAILABILITY_DELIVERIES => DeliveriesType::class,
        self::STATE_DRIVER_AVAILABILITY_WAGES => WagesType::class,
        self::STATE_DRIVER_AVAILABILITY_BONUSES => BonusesType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_START => 'domestic_survey/closing_details/start.html.twig',
        self::STATE_MISSING_DAYS => 'domestic_survey/closing_details/form-missing-days.html.twig',
        self::STATE_VEHICLE_FUEL => 'domestic_survey/closing_details/form-vehicle-fuel.html.twig',
        self::STATE_REASON_EMPTY_SURVEY => 'domestic_survey/closing_details/form-reason-for-empty-survey.html.twig',

        self::STATE_DRIVER_AVAILABILITY_DRIVERS => 'domestic_survey/closing_details/form-driver-availability-drivers.html.twig',
        self::STATE_DRIVER_AVAILABILITY_DELIVERIES => 'domestic_survey/closing_details/form-driver-availability-deliveries.html.twig',
        self::STATE_DRIVER_AVAILABILITY_WAGES => 'domestic_survey/closing_details/form-driver-availability-wages.html.twig',
        self::STATE_DRIVER_AVAILABILITY_BONUSES => 'domestic_survey/closing_details/form-driver-availability-bonuses.html.twig',

        self::STATE_CONFIRM => 'domestic_survey/closing_details/confirm.html.twig',
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
        switch ($state) {
            case self::IN_POSSESSION_OF_VEHICLE_JUMP_IN_STATE :
                return $this->subject->getIsInPossessionOfVehicle() !== SurveyResponse::IN_POSSESSION_YES;
        }
        return false;
    }
}
