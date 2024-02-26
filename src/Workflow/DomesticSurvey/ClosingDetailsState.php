<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\ClosingDetails\EarlyResponseType;
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
    public const STATE_START = 'start';
    public const STATE_EARLY_RESPONSE = 'early-response';
    public const STATE_VEHICLE_FUEL = 'vehicle-fuel';
    public const STATE_MISSING_DAYS = 'missing-days';
    public const STATE_REASON_EMPTY_SURVEY = 'empty-survey';

    public const STATE_DRIVER_AVAILABILITY_DRIVERS = 'da-drivers';
    public const STATE_DRIVER_AVAILABILITY_WAGES = 'da-wages';
    public const STATE_DRIVER_AVAILABILITY_BONUSES = 'da-bonuses';
    public const STATE_DRIVER_AVAILABILITY_DELIVERIES = 'da-deliveries';

    public const STATE_CONFIRM = 'confirm';
    public const STATE_END = 'end';

    public const IN_POSSESSION_OF_VEHICLE_JUMP_IN_STATE = self::STATE_START;

    private const array FORM_MAP = [
        self::STATE_EARLY_RESPONSE => EarlyResponseType::class,
        self::STATE_MISSING_DAYS => MissingDaysType::class,
        self::STATE_VEHICLE_FUEL => VehicleFuelType::class,
        self::STATE_REASON_EMPTY_SURVEY => ReasonEmptySurveyType::class,

        self::STATE_DRIVER_AVAILABILITY_DRIVERS => DriversAndVacanciesType::class,
        self::STATE_DRIVER_AVAILABILITY_DELIVERIES => DeliveriesType::class,
        self::STATE_DRIVER_AVAILABILITY_WAGES => WagesType::class,
        self::STATE_DRIVER_AVAILABILITY_BONUSES => BonusesType::class,
    ];

    private const array TEMPLATE_MAP = [
        self::STATE_START => 'domestic_survey/closing_details/start.html.twig',
        self::STATE_EARLY_RESPONSE => 'domestic_survey/closing_details/form-early-response.html.twig',
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

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== SurveyResponse::class) {
            throw new \InvalidArgumentException("Got " . $subject::class . ", expected " . SurveyResponse::class);
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
        return match ($state) {
            self::IN_POSSESSION_OF_VEHICLE_JUMP_IN_STATE => $this->subject->getIsInPossessionOfVehicle() !== SurveyResponse::IN_POSSESSION_YES,
            default => false,
        };
    }
}
