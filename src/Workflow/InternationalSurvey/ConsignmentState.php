<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Consignment;
use App\Form\InternationalSurvey\Consignment\CargoTypeType;
use App\Form\InternationalSurvey\Consignment\GoodsDescriptionType;
use App\Form\InternationalSurvey\Consignment\GoodsWeightType;
use App\Form\InternationalSurvey\Consignment\HazardousGoodsType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardInterface;
use InvalidArgumentException;

class ConsignmentState extends AbstractFormWizardState implements FormWizardInterface
{
    const STATE_GOODS_DESCRIPTION = 'goods-description';
    const STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    const STATE_CARGO_TYPE = 'cargo-type';
    const STATE_WEIGHT_OF_GOODS = 'weight-of-goods';
    const STATE_PLACE_OF_LOADING = 'place-of-loading';
    const STATE_PLACE_OF_UNLOADING = 'place-of-unloading';
    const STATE_ADD_ANOTHER = 'add-another';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_GOODS_DESCRIPTION => GoodsDescriptionType::class,
        self::STATE_HAZARDOUS_GOODS => HazardousGoodsType::class,
        self::STATE_CARGO_TYPE => CargoTypeType::class,
        self::STATE_WEIGHT_OF_GOODS => GoodsWeightType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_GOODS_DESCRIPTION => 'international_survey/consignment/form-goods-description.html.twig',
        self::STATE_HAZARDOUS_GOODS => 'international_survey/consignment/form-hazardous-goods.html.twig',
        self::STATE_CARGO_TYPE => 'international_survey/consignment/form-cargo-type.html.twig',
        self::STATE_WEIGHT_OF_GOODS => 'international_survey/consignment/form-weight-of-goods.html.twig',
    ];

    /** @var Consignment */
    private $subject;

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === Consignment::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . Consignment::class);
        }
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
}