<?php

namespace App\Form\InternationalSurvey\Action\DataMapper;

use App\Entity\International\Action;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class GoodsUnloadedWeightDataMapper implements DataMapperInterface
{
    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Action) {
            throw new Exception\UnexpectedTypeException($viewData, Action::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!isset($forms['weightOfGoods'])) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        // Set fields present on the form, from the entity
        $weightOfGoods = $accessor->getValue($viewData, "WeightOfGoods");
        $unloadedAll = $accessor->getValue($viewData, "WeightUnloadedAll");

        $forms['weightOfGoods']->setData($weightOfGoods);

        if (isset($forms['weightUnloadedAll'])) {
            $forms['weightUnloadedAll']->setData($unloadedAll);
        }
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof Action) {
            throw new Exception\UnexpectedTypeException($viewData, Action::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $weightOfGoods = $forms['weightOfGoods']->getData();
        $unloadedAll = false;

        if (isset($forms['weightUnloadedAll'])) {
            $unloadedAll = $forms['weightUnloadedAll']->getData();
            $weightOfGoods = $unloadedAll ? null : $weightOfGoods;
        }

        $accessor->setValue($viewData, 'WeightUnloadedAll', $unloadedAll);
        $accessor->setValue($viewData, "WeightOfGoods", $weightOfGoods);
    }
}
