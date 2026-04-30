<?php

namespace App\Workflow\RoRo;

use App\Entity\RoRo\Survey;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class RoRoState extends AbstractFormWizardState
{
    public const string STATE_INTRODUCTION = 'introduction';
    public const string STATE_DATA_ENTRY = 'data-entry';
    public const string STATE_VEHICLE_COUNTS = 'vehicle-counts';
    public const string STATE_COMMENTS = 'comments';
    public const string STATE_FINISH = 'finish';

    private Survey $subject;
    private bool $hasWizardPreviouslyCompleted = false;

    #[\Override]
    public function getSubject(): Survey
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if (!$subject instanceof Survey) {
            throw new InvalidArgumentException('Got ' . $subject::class . ', expected ' . Survey::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function determineWhetherWizardPreviouslyCompleted(): self
    {
        // This is called when the wizard gets started, and hence keeps a record
        // of whether the Survey had already been filled when the wizard was started.
        $this->hasWizardPreviouslyCompleted = ($this->subject->getIsActiveForPeriod() !== null);
        return $this;
    }

    public function hasWizardPreviouslyCompleted(): bool
    {
        return $this->hasWizardPreviouslyCompleted;
    }
}
