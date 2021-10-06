<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\AbstractGoodsDescription;
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
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;

class DayStopState extends AbstractFormWizardState implements FormWizardStateInterface
{
    const STATE_INTRO = 'introduction';
    const STATE_ORIGIN = 'stage-start';
    const STATE_DESTINATION = 'stage-end';
    const STATE_BORDER_CROSSING = 'border-crossing';
    const STATE_DISTANCE_TRAVELLED = 'distance-travelled';
    const STATE_GOODS_DESCRIPTION = 'goods-description';
    const STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    const STATE_CARGO_TYPE = 'cargo-type';
    const STATE_GOODS_WEIGHT = 'goods-weight';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_ORIGIN => OriginType::class,
        self::STATE_DESTINATION => DestinationType::class,
        self::STATE_BORDER_CROSSING => BorderCrossingType::class,
        self::STATE_DISTANCE_TRAVELLED => DistanceTravelledType::class,
        self::STATE_GOODS_DESCRIPTION => GoodsDescriptionType::class,
        self::STATE_HAZARDOUS_GOODS => HazardousGoodsType::class,
        self::STATE_CARGO_TYPE => CargoTypeType::class,
        self::STATE_GOODS_WEIGHT => GoodsWeightType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_INTRO => 'domestic_survey/day_stop/intro.html.twig',
        self::STATE_ORIGIN => 'domestic_survey/day_stop/form-origin.html.twig',
        self::STATE_DESTINATION => 'domestic_survey/day_stop/form-destination.html.twig',
        self::STATE_BORDER_CROSSING => 'domestic_survey/day_stop/form-border-crossing.html.twig',
        self::STATE_DISTANCE_TRAVELLED => 'domestic_survey/day_stop/form-distance-travelled.html.twig',
        self::STATE_GOODS_DESCRIPTION => 'domestic_survey/day_stop/form-goods-description.html.twig',
        self::STATE_HAZARDOUS_GOODS => 'domestic_survey/day_stop/form-hazardous-goods.html.twig',
        self::STATE_CARGO_TYPE => 'domestic_survey/day_stop/form-cargo-type.html.twig',
        self::STATE_GOODS_WEIGHT => 'domestic_survey/day_stop/form-goods-weight.html.twig',
    ];

    /** @var DayStop */
    private $subject;

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
                    return true;

                case self::STATE_GOODS_WEIGHT :
                    return $this->subject->getGoodsDescription() !== AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY;
            }
        } else if ($state === self::STATE_INTRO) {
            return true;
        }
        return parent::isValidAlternativeStartState($state);
    }
}