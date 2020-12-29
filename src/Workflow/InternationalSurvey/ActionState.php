<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Action;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardInterface;
use InvalidArgumentException;

class ActionState extends AbstractFormWizardState implements FormWizardInterface
{
    const STATE_PLACE = 'place';
    const STATE_GOODS_DESCRIPTION = 'goods-description';
    const STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    const STATE_CARGO_TYPE = 'cargo-type';
    const STATE_WEIGHT_LOADED = 'weight-loaded';

    const STATE_CONSIGNMENT_UNLOADED = 'consignment-unloaded';
    const STATE_WEIGHT_UNLOADED = 'weight-unloaded';

    const STATE_ADD_ANOTHER = 'add-another';
    const STATE_END = 'end';

    private const FORM_MAP = [
    ];

    private const TEMPLATE_MAP = [
    ];

    /** @var Action */
    private $subject;

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === Action::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . Action::class);
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
        if (!$this->subject instanceof Action) {
            return false;
        }

        $alternativeStartStates = [
        ];

        return $this->subject->getId() ?
            in_array($state, $alternativeStartStates) :
            false;
    }
}