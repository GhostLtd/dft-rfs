<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\AbstractGoodsDescription;
use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\BorderCrossingType;
use App\Form\DomesticSurvey\DaySummary\CargoTypeType;
use App\Form\DomesticSurvey\DaySummary\DestinationType;
use App\Form\DomesticSurvey\DaySummary\GoodsDescriptionType;
use App\Form\DomesticSurvey\DaySummary\GoodsWeightType;
use App\Form\DomesticSurvey\DaySummary\HazardousGoodsType;
use App\Form\DomesticSurvey\DaySummary\NumberOfStopsType;
use App\Form\DomesticSurvey\DaySummary\OriginType;
use App\Form\DomesticSurvey\DaySummary\DistanceTravelledType;
use App\Form\DomesticSurvey\DaySummary\FurthestStopType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;

class DaySummaryState extends AbstractFormWizardState implements FormWizardStateInterface
{
    public const STATE_INTRO = 'introduction';
    public const STATE_ORIGIN = 'day-start';
    public const STATE_DESTINATION = 'day-end';
    public const STATE_BORDER_CROSSING = 'border-crossing';
    public const STATE_DISTANCE_TRAVELLED = 'distance-travelled';
    public const STATE_FURTHEST_STOP = 'furthest-stop';
    public const STATE_GOODS_DESCRIPTION = 'goods-description';
    public const STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    public const STATE_CARGO_TYPE = 'cargo-type';
    public const STATE_GOODS_WEIGHT = 'goods-weight';
    public const STATE_NUMBER_OF_STOPS = 'number-of-stops';
    public const STATE_END = 'end';

    private const array FORM_MAP = [
        self::STATE_ORIGIN => OriginType::class,
        self::STATE_DESTINATION => DestinationType::class,
        self::STATE_BORDER_CROSSING => BorderCrossingType::class,
        self::STATE_FURTHEST_STOP => FurthestStopType::class,
        self::STATE_DISTANCE_TRAVELLED => DistanceTravelledType::class,
        self::STATE_GOODS_DESCRIPTION => GoodsDescriptionType::class,
        self::STATE_HAZARDOUS_GOODS => HazardousGoodsType::class,
        self::STATE_CARGO_TYPE => CargoTypeType::class,
        self::STATE_GOODS_WEIGHT => GoodsWeightType::class,
        self::STATE_NUMBER_OF_STOPS => NumberOfStopsType::class,
    ];

    private const array TEMPLATE_MAP = [
        self::STATE_INTRO => 'domestic_survey/day_summary/intro.html.twig',
        self::STATE_ORIGIN => 'domestic_survey/day_summary/form-origin.html.twig',
        self::STATE_DESTINATION => 'domestic_survey/day_summary/form-destination.html.twig',
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

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== DaySummary::class) {
            throw new \InvalidArgumentException("Got " . $subject::class . ", expected " . DaySummary::class);
        }
        $this->subject = $subject;
        return $this;
    }

    #[\Override]
    public function getStateFormMap()
    {
        return self::FORM_MAP;
    }

    #[\Override]
    public function getStateTemplateMap()
    {
        return self::TEMPLATE_MAP;
    }

    #[\Override]
    public function getDefaultTemplate()
    {
        return null;
    }

    #[\Override]
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
        } else if ($state === self::STATE_INTRO) {
            return true;
        }
        return parent::isValidAlternativeStartState($state);
    }

}