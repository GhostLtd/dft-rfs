<?php

namespace App\Workflow\DomesticSurvey;

use App\Entity\Domestic\DaySummary;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class DaySummaryState extends AbstractFormWizardState
{
    public const string STATE_INTRO = 'introduction';
    public const string STATE_ORIGIN = 'day-start';
    public const string STATE_DESTINATION = 'day-end';
    public const string STATE_BORDER_CROSSING = 'border-crossing';
    public const string STATE_DISTANCE_TRAVELLED = 'distance-travelled';
    public const string STATE_FURTHEST_STOP = 'furthest-stop';
    public const string STATE_GOODS_DESCRIPTION = 'goods-description';
    public const string STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    public const string STATE_CARGO_TYPE = 'cargo-type';
    public const string STATE_GOODS_WEIGHT = 'goods-weight';
    public const string STATE_NUMBER_OF_STOPS = 'number-of-stops';
    public const string STATE_END = 'end';

    private DaySummary $subject;

    #[\Override]
    public function getSubject(): DaySummary
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if (!$subject instanceof DaySummary) {
            throw new InvalidArgumentException('Got ' . ($subject ? $subject::class : 'null') . ', expected ' . DaySummary::class);
        }
        $this->subject = $subject;
        return $this;
    }
}
