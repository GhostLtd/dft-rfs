<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\ClosingDetails\MissingDaysType;
use App\Form\DomesticSurvey\ClosingDetails\ReasonEmptySurveyType;
use App\Form\DomesticSurvey\ClosingDetails\VehicleFuelType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardInterface;

class ClosingDetailsState extends AbstractFormWizardState implements FormWizardInterface
{
    const STATE_START = 'start';
    const STATE_VEHICLE_FUEL = 'vehicle-fuel';
    const STATE_MISSING_DAYS = 'missing-days';
    const STATE_REASON_EMPTY_SURVEY = 'empty-survey';
    const STATE_CONFIRM = 'confirm';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_MISSING_DAYS => MissingDaysType::class,
        self::STATE_VEHICLE_FUEL => VehicleFuelType::class,
        self::STATE_REASON_EMPTY_SURVEY => ReasonEmptySurveyType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_START => 'domestic_survey/closing_details/start.html.twig',
        self::STATE_MISSING_DAYS => 'domestic_survey/closing_details/form-missing-days.html.twig',
        self::STATE_VEHICLE_FUEL => 'domestic_survey/closing_details/form-vehicle-fuel.html.twig',
        self::STATE_REASON_EMPTY_SURVEY => 'domestic_survey/closing_details/form-reason-for-empty-survey.html.twig',
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
            case self::STATE_CONFIRM :
                return $this->subject->getIsInPossessionOfVehicle() !== SurveyResponse::IN_POSSESSION_YES;
        }
        return false;
    }
}
