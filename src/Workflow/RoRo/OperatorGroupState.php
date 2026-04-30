<?php

namespace App\Workflow\RoRo;

use App\Entity\RoRo\OperatorGroup;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class OperatorGroupState extends AbstractFormWizardState
{
    public const string STATE_CHOOSE_NAME = 'choose-name';
    public const string STATE_PREVIEW = 'preview';
    public const string STATE_FINISH = 'finish';

    private OperatorGroup $subject;
    protected string $mode;

    #[\Override]
    public function getSubject(): OperatorGroup
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if (!$subject instanceof OperatorGroup) {
            throw new InvalidArgumentException('Got ' . $subject::class . ', expected ' . OperatorGroup::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }
}
