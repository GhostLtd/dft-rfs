<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayMulti\ArrivedPortsType;
use App\Form\DomesticSurvey\DayMulti\ArrivedType;
use App\Form\DomesticSurvey\DayMulti\DepartedPortsType;
use App\Form\DomesticSurvey\DayMulti\DepartedType;
use App\Form\DomesticSurvey\DaySummary\BorderCrossingType;
use App\Form\DomesticSurvey\DaySummary\CargoTypeType;
use App\Form\DomesticSurvey\DaySummary\DestinationPortsType;
use App\Form\DomesticSurvey\DaySummary\DestinationType;
use App\Form\DomesticSurvey\DaySummary\DistanceTravelledType;
use App\Form\DomesticSurvey\DaySummary\FurthestStopType;
use App\Form\DomesticSurvey\DaySummary\GoodsDescriptionType;
use App\Form\DomesticSurvey\DaySummary\GoodsWeightType;
use App\Form\DomesticSurvey\DaySummary\HazardousGoodsType;
use App\Form\DomesticSurvey\DaySummary\OriginPortsType;
use App\Form\DomesticSurvey\DaySummary\OriginType;
use App\Workflow\FormWizardInterface;

class DayMultipleState implements FormWizardInterface
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
        self::STATE_ORIGIN => 'domestic_survey/day_multiple/form-origin.html.twig',
        self::STATE_ORIGIN_PORTS => 'domestic_survey/day_multiple/form-origin-ports.html.twig',
        self::STATE_DESTINATION => 'domestic_survey/day_multiple/form-destination.html.twig',
        self::STATE_DESTINATION_PORTS => 'domestic_survey/day_multiple/form-destination-ports.html.twig',
        self::STATE_BORDER_CROSSING => 'domestic_survey/day_multiple/form-border-crossing.html.twig',
        self::STATE_DISTANCE_TRAVELLED => 'domestic_survey/day_multiple/form-distance-travelled.html.twig',
        self::STATE_GOODS_DESCRIPTION => 'domestic_survey/day_multiple/form-goods-description.html.twig',
        self::STATE_HAZARDOUS_GOODS => 'domestic_survey/day_multiple/form-goods-description.html.twig',
        self::STATE_CARGO_TYPE => 'domestic_survey/day_multiple/form-cargo-type.html.twig',
        self::STATE_GOODS_WEIGHT => 'domestic_survey/day_multiple/form-goods-weight.html.twig',
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