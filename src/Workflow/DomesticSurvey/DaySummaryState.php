<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\AbstractGoodsDescription;
use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\BorderCrossingType;
use App\Form\DomesticSurvey\DaySummary\CargoTypeType;
use App\Form\DomesticSurvey\DaySummary\DestinationPortsType;
use App\Form\DomesticSurvey\DaySummary\DestinationType;
use App\Form\DomesticSurvey\DaySummary\GoodsDescriptionType;
use App\Form\DomesticSurvey\DaySummary\GoodsWeightType;
use App\Form\DomesticSurvey\DaySummary\HazardousGoodsType;
use App\Form\DomesticSurvey\DaySummary\NumberOfStopsType;
use App\Form\DomesticSurvey\DaySummary\OriginPortsType;
use App\Form\DomesticSurvey\DaySummary\OriginType;
use App\Form\DomesticSurvey\DaySummary\DistanceTravelledType;
use App\Form\DomesticSurvey\DaySummary\FurthestStopType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;

class DaySummaryState extends AbstractFormWizardState implements FormWizardStateInterface
{
    const STATE_ORIGIN = 'origin';
    const STATE_ORIGIN_PORTS = 'origin-ports';
    const STATE_DESTINATION = 'destination';
    const STATE_DESTINATION_PORTS = 'destination-ports';
    const STATE_BORDER_CROSSING = 'border-crossing';
    const STATE_DISTANCE_TRAVELLED = 'distance-travelled';
    const STATE_FURTHEST_STOP = 'furthest-stop';
    const STATE_GOODS_DESCRIPTION = 'goods-description';
    const STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    const STATE_CARGO_TYPE = 'cargo-type';
    const STATE_GOODS_WEIGHT = 'goods-weight';
    const STATE_NUMBER_OF_STOPS = 'number-of-stops';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_ORIGIN => OriginType::class,
        self::STATE_ORIGIN_PORTS => OriginPortsType::class,
        self::STATE_DESTINATION => DestinationType::class,
        self::STATE_DESTINATION_PORTS => DestinationPortsType::class,
        self::STATE_BORDER_CROSSING => BorderCrossingType::class,
        self::STATE_FURTHEST_STOP => FurthestStopType::class,
        self::STATE_DISTANCE_TRAVELLED => DistanceTravelledType::class,
        self::STATE_GOODS_DESCRIPTION => GoodsDescriptionType::class,
        self::STATE_HAZARDOUS_GOODS => HazardousGoodsType::class,
        self::STATE_CARGO_TYPE => CargoTypeType::class,
        self::STATE_GOODS_WEIGHT => GoodsWeightType::class,
        self::STATE_NUMBER_OF_STOPS => NumberOfStopsType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_ORIGIN => 'domestic_survey/day_summary/form-origin.html.twig',
        self::STATE_ORIGIN_PORTS => 'domestic_survey/day_summary/form-origin-ports.html.twig',
        self::STATE_DESTINATION => 'domestic_survey/day_summary/form-destination.html.twig',
        self::STATE_DESTINATION_PORTS => 'domestic_survey/day_summary/form-destination-ports.html.twig',
        self::STATE_BORDER_CROSSING => 'domestic_survey/day_summary/form-border-crossing.html.twig',
        self::STATE_FURTHEST_STOP => 'domestic_survey/day_summary/form-furthest-stop.html.twig',
        self::STATE_DISTANCE_TRAVELLED => 'domestic_survey/day_summary/form-distance-travelled.html.twig',
        self::STATE_GOODS_DESCRIPTION => 'domestic_survey/day_summary/form-goods-description.html.twig',
        self::STATE_HAZARDOUS_GOODS => 'domestic_survey/day_summary/form-hazardous-goods.html.twig',
        self::STATE_CARGO_TYPE => 'domestic_survey/day_summary/form-cargo-type.html.twig',
        self::STATE_GOODS_WEIGHT => 'domestic_survey/day_summary/form-goods-weight.html.twig',
        self::STATE_NUMBER_OF_STOPS => 'domestic_survey/day_summary/form-number-of-stops.html.twig',
    ];

    /** @var DaySummary */
    private $subject;

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
        if ($this->subject->getId()) {
            switch($state) {
                case self::STATE_ORIGIN:
                case self::STATE_DISTANCE_TRAVELLED:
                case self::STATE_GOODS_DESCRIPTION:
                case self::STATE_GOODS_WEIGHT :
                case self::STATE_NUMBER_OF_STOPS :
                    return true;
            }
        }
        return parent::isValidAlternativeStartState($state);
    }

}