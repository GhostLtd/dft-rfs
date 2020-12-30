<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Action;
use App\Form\InternationalSurvey\Action\AddAnotherType;
use App\Form\InternationalSurvey\Action\CargoTypeType;
use App\Form\InternationalSurvey\Action\GoodsDescriptionType;
use App\Form\InternationalSurvey\Action\GoodsLoadedWeightType;
use App\Form\InternationalSurvey\Action\GoodsUnloadedWeightType;
use App\Form\InternationalSurvey\Action\HazardousGoodsType;
use App\Form\InternationalSurvey\Action\LoadingPlaceType;
use App\Form\InternationalSurvey\Action\PlaceType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardInterface;
use InvalidArgumentException;

class ActionState extends AbstractFormWizardState implements FormWizardInterface
{
    const STATE_PLACE = 'place';
    const STATE_GOODS_DESCRIPTION = 'goods-description';
    const STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    const STATE_CARGO_TYPE = 'cargo-type';
    const STATE_WEIGHT_LOADED = 'weight-loaded';

    const STATE_CONSIGNMENT_UNLOADED = 'consignment-unloaded';
    const STATE_WEIGHT_UNLOADED = 'weight-unloaded';

    const STATE_ADD_ANOTHER = 'add-another';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_PLACE => PlaceType::class,
        self::STATE_GOODS_DESCRIPTION => GoodsDescriptionType::class,
        self::STATE_HAZARDOUS_GOODS => HazardousGoodsType::class,
        self::STATE_CARGO_TYPE => CargoTypeType::class,
        self::STATE_WEIGHT_LOADED => GoodsLoadedWeightType::class,
        self::STATE_WEIGHT_UNLOADED => GoodsUnloadedWeightType::class,

        self::STATE_CONSIGNMENT_UNLOADED => LoadingPlaceType::class,

        self::STATE_ADD_ANOTHER => AddAnotherType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_PLACE => 'international_survey/action/form-place.html.twig',
        self::STATE_GOODS_DESCRIPTION => 'international_survey/action/form-goods-description.html.twig',
        self::STATE_HAZARDOUS_GOODS => 'international_survey/action/form-hazardous-goods.html.twig',
        self::STATE_CARGO_TYPE => 'international_survey/action/form-cargo-type.html.twig',
        self::STATE_WEIGHT_LOADED => 'international_survey/action/form-weight-loaded.html.twig',
        self::STATE_WEIGHT_UNLOADED => 'international_survey/action/form-weight-unloaded.html.twig',
        self::STATE_CONSIGNMENT_UNLOADED => 'international_survey/action/form-unloaded.html.twig',
        self::STATE_ADD_ANOTHER => 'international_survey/action/form-add-another.html.twig',
    ];

    /** @var Action */
    private $subject;

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === Action::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . Action::class);
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

    public function isValidAlternativeStartState($state): bool
    {
        if (!$this->subject instanceof Action) {
            return false;
        }

        $alternativeStartStates = [
        ];

        return $this->subject->getId() ?
            in_array($state, $alternativeStartStates) :
            false;
    }
}