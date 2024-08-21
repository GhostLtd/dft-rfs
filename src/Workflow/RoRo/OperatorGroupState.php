<?php

namespace App\Workflow\RoRo;

use App\Entity\RoRo\OperatorGroup;
use App\Form\Admin\RoRo\OperatorGroupType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class OperatorGroupState extends AbstractFormWizardState implements FormWizardStateInterface
{
    public const STATE_CHOOSE_NAME = 'choose-name';
    public const STATE_PREVIEW = 'preview';
    public const STATE_FINISH = 'finish';

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
        if ($subject::class !== OperatorGroup::class) {
            throw new InvalidArgumentException("Got " . $subject::class . ", expected " . OperatorGroup::class);
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

    #[\Override]
    public function getStateFormMap(): array
    {
        return [
            self::STATE_CHOOSE_NAME => OperatorGroupType::class,
        ];
    }

    #[\Override]
    public function getStateTemplateMap(): array
    {
        return [
            self::STATE_CHOOSE_NAME => 'admin/roro/operator-groups/add/form-choose-name.html.twig',
            self::STATE_PREVIEW => 'admin/roro/operator-groups/add/form-preview.html.twig',
        ];
    }

    #[\Override]
    public function getDefaultTemplate(): ?string
    {
        return null;
    }

    #[\Override]
    public function isValidAlternativeStartState($state): bool
    {
        return false;
    }
}