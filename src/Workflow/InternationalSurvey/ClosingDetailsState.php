<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Survey;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class ClosingDetailsState extends AbstractFormWizardState
{
    public const string STATE_START = 'start';
    public const string STATE_EARLY_RESPONSE = 'early-response';
    public const string STATE_REASON_EMPTY_SURVEY = 'empty-survey';
    public const string STATE_LOADING_WITHOUT_UNLOADING = 'loading-without-unloading';
    public const string STATE_CONFIRM = 'confirm';
    public const string STATE_END = 'end';

    private Survey $subject;

    private ?bool $isCorrect = null;

    public function getSubject(): ?Survey
    {
        return $this->subject ?? null;
    }

    public function setSubject($subject): self
    {
        if (!$subject instanceof Survey) {
            throw new InvalidArgumentException('Got ' . $subject::class . ', expected ' . Survey::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function getIsCorrect(): ?bool { return $this->isCorrect; }
    public function setIsCorrect(?bool $value): self { $this->isCorrect = $value; return $this; }
}
