<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\ClosingDetails\ReasonEmptySurveyType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class ClosingDetailsState extends AbstractFormWizardState implements FormWizardStateInterface
{
    const STATE_START = 'start';
    const STATE_REASON_EMPTY_SURVEY = 'empty-survey';
    const STATE_CONFIRM = 'confirm';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_REASON_EMPTY_SURVEY => ReasonEmptySurveyType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_REASON_EMPTY_SURVEY => 'international_survey/closing_details/form-reason-for-empty-survey.html.twig',
        self::STATE_CONFIRM => 'international_survey/closing_details/confirm.html.twig',
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
        switch ($state) {
            case self::STATE_CONFIRM :

        }
        return false;
    }
}
