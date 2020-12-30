<?php

namespace App\Form\InternationalSurvey\Action\DataMapper;

use App\Entity\International\Action;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class GoodsUnloadedWeightDataMapper implements DataMapperInterface
{
    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Action) {
            throw new Exception\UnexpectedTypeException($viewData, Action::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (!isset($forms['unloadedAll']) || !isset($forms['weightOfGoods'])) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $fullWeightOfGoods = $this->getFullWeightOfGoods($viewData);

        $weightOfGoods = $accessor->getValue($viewData, "WeightOfGoods");

        if ($weightOfGoods === null) {
            $unloadedAll = null;
        } else {
            $unloadedAll = $weightOfGoods === $fullWeightOfGoods;

            if ($unloadedAll) {
                $weightOfGoods = null;
            }
        }

        $forms['unloadedAll']->setData($unloadedAll);
        $forms['weightOfGoods']->setData($weightOfGoods);
    }

    public function mapFormsToData($forms, &$viewData)
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (!$viewData instanceof Action) {
            throw new Exception\UnexpectedTypeException($viewData, Action::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $unloadedAll = $forms['unloadedAll']->getData();
        $weightOfGoods = $unloadedAll ? $this->getFullWeightOfGoods($viewData) : $forms['weightOfGoods']->getData();

        $accessor->setValue($viewData, "WeightOfGoods", $weightOfGoods);
    }

    protected function getFullWeightOfGoods(Action $action): ?int {
        $loadingAction = $action->getLoadingAction();
        return $loadingAction ? $loadingAction->getWeightOfGoods() : null;
    }
}