<?php

namespace App\Workflow\RoRo;

use App\Entity\RoRo\OperatorGroup;
use App\Form\Admin\RoRo\OperatorGroupType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class OperatorGroupState extends AbstractFormWizardState implements FormWizardStateInterface
{
    const STATE_CHOOSE_NAME = 'choose-name';
    const STATE_PREVIEW = 'preview';
    const STATE_FINISH = 'finish';

    private OperatorGroup $subject;
    protected string $mode;

    public function getSubject(): OperatorGroup
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (get_class($subject) !== OperatorGroup::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . OperatorGroup::class);
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

    public function getStateFormMap(): array
    {
        return [
            self::STATE_CHOOSE_NAME => OperatorGroupType::class,
        ];
    }

    public function getStateTemplateMap(): array
    {
        return [
            self::STATE_CHOOSE_NAME => 'admin/roro/operator-groups/add/form-choose-name.html.twig',
            self::STATE_PREVIEW => 'admin/roro/operator-groups/add/form-preview.html.twig',
        ];
    }

    public function getDefaultTemplate(): ?string
    {
        return null;
    }

    public function isValidAlternativeStartState($state): bool
    {
        return false;
    }
}