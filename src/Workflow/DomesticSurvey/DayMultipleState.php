<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayMulti\ArrivedPortsType;
use App\Form\DomesticSurvey\DayMulti\ArrivedType;
use App\Form\DomesticSurvey\DayMulti\DepartedPortsType;
use App\Form\DomesticSurvey\DayMulti\DepartedType;
use App\Workflow\FormWizardInterface;

class DayMultipleState implements FormWizardInterface
{
    const STATE_DEPARTED = 'departed';
    const STATE_DEPARTED_PORTS = 'departed-ports';
    const STATE_ARRIVED = 'arrived';
    const STATE_ARRIVED_PORTS = 'arrived-ports';
    const STATE_NEXT = 'next';
    const STATE_ = '';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_DEPARTED => DepartedType::class,
        self::STATE_DEPARTED_PORTS => DepartedPortsType::class,
        self::STATE_ARRIVED => ArrivedType::class,
        self::STATE_ARRIVED_PORTS => ArrivedPortsType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_DEPARTED => 'domestic_survey/day_multiple/form-departed.html.twig',
        self::STATE_DEPARTED_PORTS => 'domestic_survey/day_multiple/form-departed-ports.html.twig',
        self::STATE_ARRIVED => 'domestic_survey/day_multiple/form-arrived.html.twig',
        self::STATE_ARRIVED_PORTS => 'domestic_survey/day_multiple/form-arrived-ports.html.twig',
    ];

    private $state = self::STATE_DEPARTED;

    /** @var DayStop */
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
        if (!get_class($subject) === DayStop::class) throw new \InvalidArgumentException("Got " . get_class($subject) . ", expected " . DayStop::class);
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

        if ($this->subject->getOriginLocation()) {
            $states[] = $this->subject->getGoodsLoaded() ? self::STATE_DEPARTED_PORTS : self::STATE_ARRIVED;
        }
        if (in_array(self::STATE_DEPARTED_PORTS, $states) && $this->subject->getGoodsTransferredFrom() !== Day::TRANSFERRED) {
            $states[] = self::STATE_ARRIVED;
        }

        if ($this->subject->getDestinationLocation()) {
            $states[] = $this->subject->getGoodsUnloaded() ? self::STATE_ARRIVED_PORTS : self::STATE_NEXT;
        }
        if (in_array(self::STATE_ARRIVED_PORTS, $states) && $this->subject->getGoodsTransferredTo() !== Day::TRANSFERRED) {
            $states[] = self::STATE_NEXT;
        }

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