<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Survey;
use App\Form\InternationalSurvey\ClosingDetails\EarlyResponseType;
use App\Form\InternationalSurvey\ClosingDetails\LoadingWithoutUnloadingType;
use App\Form\InternationalSurvey\ClosingDetails\ReasonEmptySurveyType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class ClosingDetailsState extends AbstractFormWizardState implements FormWizardStateInterface
{
    public const STATE_START = 'start';
    public const STATE_EARLY_RESPONSE = 'early-response';
    public const STATE_REASON_EMPTY_SURVEY = 'empty-survey';
    public const STATE_LOADING_WITHOUT_UNLOADING = 'loading-without-unloading';
    public const STATE_CONFIRM = 'confirm';
    public const STATE_END = 'end';

    private const array FORM_MAP = [
        self::STATE_EARLY_RESPONSE => EarlyResponseType::class,
        self::STATE_REASON_EMPTY_SURVEY => ReasonEmptySurveyType::class,
        self::STATE_LOADING_WITHOUT_UNLOADING => LoadingWithoutUnloadingType::class,
    ];

    private const array TEMPLATE_MAP = [
        self::STATE_CONFIRM => 'international_survey/closing_details/confirm.html.twig',
        self::STATE_EARLY_RESPONSE => 'international_survey/closing_details/form-early-response.html.twig',
        self::STATE_REASON_EMPTY_SURVEY => 'international_survey/closing_details/form-reason-for-empty-survey.html.twig',
        self::STATE_LOADING_WITHOUT_UNLOADING => 'international_survey/closing_details/form-loading-without-unloading.html.twig',
    ];

    /** @var Survey */
    private $subject;

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== Survey::class) {
            throw new InvalidArgumentException("Got " . $subject::class . ", expected " . Survey::class);
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
        return false;
    }
}
