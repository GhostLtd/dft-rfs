<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Action;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class ActionState extends AbstractFormWizardState
{
    public const string STATE_PLACE = 'place';
    public const string STATE_GOODS_DESCRIPTION = 'goods-description';
    public const string STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    public const string STATE_CARGO_TYPE = 'cargo-type';
    public const string STATE_WEIGHT_LOADED = 'weight-loaded';
    public const string STATE_CONSIGNMENT_UNLOADED = 'consignment-unloaded';
    public const string STATE_WEIGHT_UNLOADED = 'weight-unloaded';
    public const string STATE_ADD_ANOTHER = 'add-another';
    public const string STATE_END = 'end';

    private Action $subject;

    private ?bool $confirm = null;

    public function getSubject(): ?Action
    {
        return $this->subject ?? null;
    }

    public function setSubject($subject): self
    {
        if (!$subject instanceof Action) {
            throw new InvalidArgumentException('Got ' . $subject::class . ', expected ' . Action::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function getConfirm(): ?bool { return $this->confirm; }
    public function setConfirm(?bool $value): self { $this->confirm = $value; return $this; }
}
