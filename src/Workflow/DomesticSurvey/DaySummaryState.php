<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DayMulti\ArrivedPortsType;
use App\Form\DomesticSurvey\DayMulti\ArrivedType;
use App\Form\DomesticSurvey\DayMulti\DepartedPortsType;
use App\Form\DomesticSurvey\DayMulti\DepartedType;
use App\Form\DomesticSurvey\DaySummary\OriginType;
use App\Workflow\FormWizardInterface;

class DaySummaryState implements FormWizardInterface
{
    const STATE_ORIGIN = 'origin';
    const STATE_ORIGIN_PORTS = 'origin-ports';
    const STATE_DESTINATION = 'destination';
    const STATE_DESTINATION_PORTS = 'destination-ports';
    const STATE_NEXT = 'next';
    const STATE_ = '';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_ORIGIN => OriginType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_ORIGIN => 'domestic_survey/day_summary/form-origin.html.twig',
        self::STATE_ORIGIN_PORTS => 'domestic_survey/day_summary/form-origin-ports.html.twig',
        self::STATE_DESTINATION => 'domestic_survey/day_summary/form-destination.html.twig',
        self::STATE_DESTINATION_PORTS => 'domestic_survey/day_summary/form-destination-ports.html.twig',
    ];

    private $state = self::STATE_ORIGIN;

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
        if (!get_class($subject) === DaySummary::class) throw new \InvalidArgumentException("Got " . get_class($subject) . ", expected " . DaySummary::class);
        $this->subject = $subject;
        return $this;
    }

    public function isValidJumpInState($state)
    {
        return (in_array($state, $this->getValidJumpInStates()));
    }

    protected function getValidJumpInStates()
    {
        $states = [self::STATE_ORIGIN];

        if ($this->subject->getOriginLocation()) {
            $states[] = $this->subject->getGoodsLoaded() ? self::STATE_ORIGIN_PORTS : self::STATE_DESTINATION;
        }
        if (in_array(self::STATE_ORIGIN_PORTS, $states) && $this->subject->getGoodsTransferredFrom() !== Day::TRANSFERRED) {
            $states[] = self::STATE_DESTINATION;
        }

        if ($this->subject->getDestinationLocation()) {
            $states[] = $this->subject->getGoodsUnloaded() ? self::STATE_DESTINATION_PORTS : self::STATE_NEXT;
        }
        if (in_array(self::STATE_DESTINATION_PORTS, $states) && $this->subject->getGoodsTransferredTo() !== Day::TRANSFERRED) {
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