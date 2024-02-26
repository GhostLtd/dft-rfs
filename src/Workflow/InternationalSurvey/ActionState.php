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
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class ActionState extends AbstractFormWizardState implements FormWizardStateInterface
{
    public const STATE_PLACE = 'place';
    public const STATE_GOODS_DESCRIPTION = 'goods-description';
    public const STATE_HAZARDOUS_GOODS = 'hazardous-goods';
    public const STATE_CARGO_TYPE = 'cargo-type';
    public const STATE_WEIGHT_LOADED = 'weight-loaded';

    public const STATE_CONSIGNMENT_UNLOADED = 'consignment-unloaded';
    public const STATE_WEIGHT_UNLOADED = 'weight-unloaded';

    public const STATE_ADD_ANOTHER = 'add-another';
    public const STATE_END = 'end';

    private const array FORM_MAP = [
        self::STATE_PLACE => PlaceType::class,
        self::STATE_GOODS_DESCRIPTION => GoodsDescriptionType::class,
        self::STATE_HAZARDOUS_GOODS => HazardousGoodsType::class,
        self::STATE_CARGO_TYPE => CargoTypeType::class,
        self::STATE_WEIGHT_LOADED => GoodsLoadedWeightType::class,
        self::STATE_WEIGHT_UNLOADED => GoodsUnloadedWeightType::class,

        self::STATE_CONSIGNMENT_UNLOADED => LoadingPlaceType::class,

        self::STATE_ADD_ANOTHER => AddAnotherType::class,
    ];

    private const array TEMPLATE_MAP = [
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

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== Action::class) {
            throw new InvalidArgumentException("Got " . $subject::class . ", expected " . Action::class);
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
        if (!$this->subject instanceof Action) {
            return false;
        }

        $alternativeStartStates = $this->getSubject()->getLoading()
            ? [
                self::STATE_GOODS_DESCRIPTION,
                self::STATE_HAZARDOUS_GOODS,
            ] : [
                self::STATE_CONSIGNMENT_UNLOADED,
            ];

        return $this->subject->getId() ?
            in_array($state, $alternativeStartStates) :
            // Add another needs to be a jump in state, because we clear the session while rendering the add another page
            $state === self::STATE_ADD_ANOTHER;
    }
}