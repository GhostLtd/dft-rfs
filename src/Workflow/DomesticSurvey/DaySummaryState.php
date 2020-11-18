<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DayMulti\ArrivedPortsType;
use App\Form\DomesticSurvey\DayMulti\ArrivedType;
use App\Form\DomesticSurvey\DayMulti\DepartedPortsType;
use App\Form\DomesticSurvey\DayMulti\DepartedType;
use App\Form\DomesticSurvey\DaySummary\DestinationPortsType;
use App\Form\DomesticSurvey\DaySummary\DestinationType;
use App\Form\DomesticSurvey\DaySummary\DistanceTravelledType;
use App\Form\DomesticSurvey\DaySummary\FurthestStopType;
use App\Form\DomesticSurvey\DaySummary\GoodsDescriptionType;
use App\Form\DomesticSurvey\DaySummary\OriginPortsType;
use App\Form\DomesticSurvey\DaySummary\OriginType;
use App\Workflow\FormWizardInterface;

class DaySummaryState implements FormWizardInterface
{
    const STATE_ORIGIN = 'origin';
    const STATE_ORIGIN_PORTS = 'origin-ports';
    const STATE_DESTINATION = 'destination';
    const STATE_DESTINATION_PORTS = 'destination-ports';
    const STATE_DISTANCE_TRAVELLED = 'distance-travelled';
    const STATE_FURTHEST_STOP = 'furthest-stop';
    const STATE_GOODS = 'goods';
    const STATE_ = '';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_ORIGIN => OriginType::class,
        self::STATE_ORIGIN_PORTS => OriginPortsType::class,
        self::STATE_DESTINATION => DestinationType::class,
        self::STATE_DESTINATION_PORTS => DestinationPortsType::class,
        self::STATE_FURTHEST_STOP => FurthestStopType::class,
        self::STATE_DISTANCE_TRAVELLED => DistanceTravelledType::class,
        self::STATE_GOODS => GoodsDescriptionType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_ORIGIN => 'domestic_survey/day_summary/form-origin.html.twig',
        self::STATE_ORIGIN_PORTS => 'domestic_survey/day_summary/form-origin-ports.html.twig',
        self::STATE_DESTINATION => 'domestic_survey/day_summary/form-destination.html.twig',
        self::STATE_DESTINATION_PORTS => 'domestic_survey/day_summary/form-destination-ports.html.twig',
        self::STATE_FURTHEST_STOP => 'domestic_survey/day_summary/form-furthest-stop.html.twig',
        self::STATE_DISTANCE_TRAVELLED => 'domestic_survey/day_summary/form-distance-travelled.html.twig',
        self::STATE_GOODS => 'domestic_survey/day_summary/form-goods.html.twig',
    ];

    private $state = self::STATE_ORIGIN;

    /** @var DaySummary */
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
            $states[] = $this->subject->getGoodsUnloaded() ? self::STATE_DESTINATION_PORTS : self::STATE_GOODS;
        }
        if (in_array(self::STATE_DESTINATION_PORTS, $states) && $this->subject->getGoodsTransferredTo() !== Day::TRANSFERRED) {
            $states[] = self::STATE_FURTHEST_STOP;
        }

        if ($this->subject->getFurthestStop()) {
            $stated[] = self::STATE_DISTANCE_TRAVELLED;
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