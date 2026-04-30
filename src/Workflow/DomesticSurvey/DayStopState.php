<?php

namespace App\Workflow\DomesticSurvey;

use App\Entity\Domestic\DayStop;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class DayStopState extends AbstractFormWizardState
{
    public const string STATE_INTRO = 'introduction';
    public const string STATE_ORIGIN = 'stage-start';
    public const string STATE_DESTINATION = 'stage-end';
    public const string STATE_BORDER_CROSSING = 'border-crossing';
    public const string STATE_DISTANCE_TRAVELLED = 'distance-travelled';
    public const string STATE_GOODS_DESCRIPTION = 'goods-description';
    public const string STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    public const string STATE_CARGO_TYPE = 'cargo-type';
    public const string STATE_GOODS_WEIGHT = 'goods-weight';
    public const string STATE_END = 'end';

    private DayStop $subject;

    #[\Override]
    public function getSubject(): DayStop
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if (!$subject instanceof DayStop) {
            throw new InvalidArgumentException('Got ' . ($subject ? $subject::class : 'null') . ', expected ' . DayStop::class);
        }
        $this->subject = $subject;
        return $this;
    }
}
