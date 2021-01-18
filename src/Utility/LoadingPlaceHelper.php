<?php

namespace App\Utility;

use App\Entity\AbstractGoodsDescription;
use App\Entity\International\Action;
use App\Repository\International\ActionRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoadingPlaceHelper
{
    protected $actionRepository;
    protected $translator;
    protected $requestStack;

    public function __construct(ActionRepository $actionRepository, TranslatorInterface $translator, RequestStack $requestStack)
    {
        $this->actionRepository = $actionRepository;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    public function getChoicesAndOptionsForPlace(Action $action, bool $extendedHints=false): array
    {
        $tripId = $action->getTrip()->getId();
        $loadingActions = $this->actionRepository->getLoadingActions($tripId);

        $currentLoadingAction = $action->getLoadingAction();

        $choices = [];
        $choiceOptions = [];
        foreach ($loadingActions as $loadingAction) {
            if ($currentLoadingAction && $loadingAction->getId() === $currentLoadingAction->getId()) {
                $loadingAction = $currentLoadingAction;
            }

            $label = $this->getLabelForLoadingAction($loadingAction);

            $weights = $this->getUnloadingSummary($loadingAction, $action);
            $choices[$label] = $loadingAction;

            if ($weights['isFullyUnloaded']) {
                $choiceOptions[$label] = [
                    'disabled' => true,
                    'help' => 'common.action.fully-unloaded',
                ];
            } else if ($extendedHints) {
                if ($weights['unloaded'] === 0) {
                    $choiceOptions[$label] = [
                        'help_translation_parameters' => $weights,
                        'help' => 'common.action.weight-none-unloaded'
                    ];
                } else {
                    $choiceOptions[$label] = [
                        'help_translation_parameters' => $weights,
                        'help' => ($weights['isFullyUnloadedByWeight']) ?
                            'common.action.weight-fully-unloaded' :
                            'common.action.weight-partially-unloaded',
                    ];
                }
            }
        }

        return [$choices, $choiceOptions];
    }

    public function getUnloadingSummary(Action $loadingAction, ?Action $excludeAction = null): array
    {
        $isFullyUnloaded = false;
        $unloadedWeight = 0;

        foreach($loadingAction->getUnloadingActions() as $unloadingAction) {
            if ($unloadingAction === $excludeAction) {
                continue;
            }

            if ($unloadingAction->getWeightUnloadedAll()) {
                $isFullyUnloaded = true;
            } else {
                $unloadedWeight += $unloadingAction->getWeightOfGoods();
            }
        }

        $loadedWeight = $loadingAction->getWeightOfGoods();
        $remainingWeight = max($loadedWeight - $unloadedWeight, 0);
        return [
            'isFullyUnloaded' => $isFullyUnloaded,
            'isFullyUnloadedByWeight' => $remainingWeight === 0,
            'loaded' => $loadedWeight,
            'unloaded' => $unloadedWeight,
            'remaining' => $remainingWeight,
        ];
    }

    protected function getLabelForLoadingAction(Action $action): string
    {
        $country = Countries::getName(strtoupper($action->getCountry()), $this->requestStack->getCurrentRequest()->getLocale());

        $goods = $action->getGoodsDescription() === AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER ?
            $action->getGoodsDescriptionOther() :
            $this->translator->trans("goods.description.options.{$action->getGoodsDescription()}");

        return $this->translator->trans('international.action.stop', [
            'place' => $action->getName(),
            'country' => $country,
            'number' => $action->getNumber(),
            'goods' => $goods,
        ]);
    }
}