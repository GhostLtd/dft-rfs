<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\DomesticStopMultiple;
use App\Form\DomesticSurvey\DayMulti\DepartedType;
use App\Workflow\FormWizardInterface;

class DayMultipleState implements FormWizardInterface
{
    const STATE_DEPARTED = 'departed';
    const STATE_DEPARTED_MODE_CHANGE = 'departed-mode-change';
    const STATE_ARRIVED = 'arrived';
    const STATE_ARRIVED_MODE_CHANGE = 'arrived-mode-change';
    const STATE_NEXT = 'next';
    const STATE_ = '';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_DEPARTED => DepartedType::class,
        self::STATE_DEPARTED_MODE_CHANGE => '',
    ];

    private const TEMPLATE_MAP = [
    ];

    private $state = self::STATE_DEPARTED;

    /** @var DomesticStopMultiple */
    private $subject;

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     * @return self
     */
    public function setState($state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === DomesticStopMultiple::class) throw new \InvalidArgumentException("Got " . get_class($subject) . ", expected " . DomesticStopMultiple::class);
        $this->subject = $subject;
        return $this;
    }

    public function isValidJumpInState($state)
    {
        return (in_array($state, $this->getValidJumpInStates()));
    }

    protected function getValidJumpInStates()
    {
        $states = [self::STATE_DEPARTED];

        return $states;
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
}