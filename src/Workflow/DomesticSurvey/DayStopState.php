<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\BorderCrossingType;
use App\Form\DomesticSurvey\DayStop\CargoTypeType;
use App\Form\DomesticSurvey\DayStop\DestinationPortsType;
use App\Form\DomesticSurvey\DayStop\DestinationType;
use App\Form\DomesticSurvey\DayStop\DistanceTravelledType;
use App\Form\DomesticSurvey\DayStop\GoodsDescriptionType;
use App\Form\DomesticSurvey\DayStop\GoodsWeightType;
use App\Form\DomesticSurvey\DayStop\HazardousGoodsType;
use App\Form\DomesticSurvey\DayStop\OriginPortsType;
use App\Form\DomesticSurvey\DayStop\OriginType;
use App\Workflow\FormWizardInterface;

class DayStopState implements FormWizardInterface
{
    const STATE_ORIGIN = 'origin';
    const STATE_ORIGIN_PORTS = 'origin-ports';
    const STATE_DESTINATION = 'destination';
    const STATE_DESTINATION_PORTS = 'destination-ports';
    const STATE_BORDER_CROSSING = 'border-crossing';
    const STATE_DISTANCE_TRAVELLED = 'distance-travelled';
    const STATE_GOODS_DESCRIPTION = 'goods-description';
    const STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    const STATE_CARGO_TYPE = 'cargo-type';
    const STATE_GOODS_WEIGHT = 'goods-weight';
    const STATE_ = '';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_ORIGIN => OriginType::class,
        self::STATE_ORIGIN_PORTS => OriginPortsType::class,
        self::STATE_DESTINATION => DestinationType::class,
        self::STATE_DESTINATION_PORTS => DestinationPortsType::class,
        self::STATE_BORDER_CROSSING => BorderCrossingType::class,
        self::STATE_DISTANCE_TRAVELLED => DistanceTravelledType::class,
        self::STATE_GOODS_DESCRIPTION => GoodsDescriptionType::class,
        self::STATE_HAZARDOUS_GOODS => HazardousGoodsType::class,
        self::STATE_CARGO_TYPE => CargoTypeType::class,
        self::STATE_GOODS_WEIGHT => GoodsWeightType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_ORIGIN => 'domestic_survey/day_stop/form-origin.html.twig',
        self::STATE_ORIGIN_PORTS => 'domestic_survey/day_stop/form-origin-ports.html.twig',
        self::STATE_DESTINATION => 'domestic_survey/day_stop/form-destination.html.twig',
        self::STATE_DESTINATION_PORTS => 'domestic_survey/day_stop/form-destination-ports.html.twig',
        self::STATE_BORDER_CROSSING => 'domestic_survey/day_stop/form-border-crossing.html.twig',
        self::STATE_DISTANCE_TRAVELLED => 'domestic_survey/day_stop/form-distance-travelled.html.twig',
        self::STATE_GOODS_DESCRIPTION => 'domestic_survey/day_stop/form-goods-description.html.twig',
        self::STATE_HAZARDOUS_GOODS => 'domestic_survey/day_stop/form-hazardous-goods.html.twig',
        self::STATE_CARGO_TYPE => 'domestic_survey/day_stop/form-cargo-type.html.twig',
        self::STATE_GOODS_WEIGHT => 'domestic_survey/day_stop/form-goods-weight.html.twig',
    ];

    private $state;

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
        $states = [self::STATE_ORIGIN];

        if ($this->subject->getOriginLocation()) {
            $states[] = $this->subject->getGoodsLoaded() ? self::STATE_ORIGIN_PORTS : self::STATE_DESTINATION;
        }
        if (in_array(self::STATE_ORIGIN_PORTS, $states) && in_array($this->subject->getGoodsTransferredFrom(), Day::TRANSFER_CHOICES)) {
            $states[] = self::STATE_DESTINATION;
        }

        if ($this->subject->getDestinationLocation()) {
            $states[] = $this->subject->getGoodsUnloaded()
                ? self::STATE_DESTINATION_PORTS
                : ($this->subject->isNorthernIrelandSurvey()
                    ? self::STATE_BORDER_CROSSING
                    : self::STATE_DISTANCE_TRAVELLED);
        }
        if (in_array(self::STATE_DESTINATION_PORTS, $states) && in_array($this->subject->getGoodsTransferredTo(), Day::TRANSFER_CHOICES)) {
            $states[] = $this->subject->isNorthernIrelandSurvey()
                ? self::STATE_BORDER_CROSSING
                : self::STATE_DISTANCE_TRAVELLED;
        }

        if ($this->subject->isNorthernIrelandSurvey() && $this->subject->getBorderCrossingLocation()) {
            $states[] = self::STATE_DISTANCE_TRAVELLED;
        }

        if (in_array(self::STATE_DISTANCE_TRAVELLED, $states) && $this->subject->getDistanceTravelled()) {
            $states[] = self::STATE_GOODS_DESCRIPTION;
        }

        if (in_array(self::STATE_GOODS_DESCRIPTION, $states) && $this->subject->getGoodsDescription()) {
            $states[] = self::STATE_HAZARDOUS_GOODS;
        }

        if (in_array(self::STATE_HAZARDOUS_GOODS, $states) && $this->subject->getGoodsDescription()) {
            $states[] = self::STATE_CARGO_TYPE;
        }

        if (in_array(self::STATE_CARGO_TYPE, $states) && $this->subject->getGoodsDescription()) {
            $states[] = self::STATE_GOODS_WEIGHT;
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