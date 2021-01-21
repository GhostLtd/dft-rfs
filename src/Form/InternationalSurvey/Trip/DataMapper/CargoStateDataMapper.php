<?php

namespace App\Form\InternationalSurvey\Trip\DataMapper;

use App\Entity\International\Trip;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CargoStateDataMapper implements DataMapperInterface
{
    protected $direction;

    public function __construct(string $direction)
    {
        $this->direction = $direction;
    }

    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        if (!isset($forms['wasEmpty']) || !isset($forms['wasLimitedBy'])) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $wasEmpty = $accessor->getValue($viewData, "{$this->direction}WasEmpty");
        $wasLimitedBy = [];

        if ($limitedBySpace = $accessor->getValue($viewData, "{$this->direction}WasLimitedBySpace")) {
            $wasLimitedBy[] = 'space';
        }

        if ($limitedByWeight = $accessor->getValue($viewData, "{$this->direction}WasLimitedByWeight")) {
            $wasLimitedBy[] = 'weight';
        }

        $forms['wasEmpty']->setData($wasEmpty);
        $forms['wasLimitedBy']->setData($wasLimitedBy);
        $forms['wasAtCapacity']->setData(is_null($limitedBySpace) && is_null($limitedByWeight) ? null : count($wasLimitedBy) > 0);
    }

    public function mapFormsToData($forms, &$viewData)
    {
        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $wasAtCapacity = $forms['wasAtCapacity']->getData();

        if ($wasAtCapacity === false) {
            $wasEmpty = $forms['wasEmpty']->getData();
            $accessor->setValue($viewData, "{$this->direction}WasEmpty", $wasEmpty);
            $accessor->setValue($viewData, "{$this->direction}WasLimitedBySpace", false);
            $accessor->setValue($viewData, "{$this->direction}WasLimitedByWeight", false);
        } elseif ($wasAtCapacity === true) {
            $wasLimitedBy = $forms['wasLimitedBy']->getData();
            $accessor->setValue($viewData, "{$this->direction}WasEmpty", false);
            $accessor->setValue($viewData, "{$this->direction}WasLimitedBySpace", in_array('space', $wasLimitedBy));
            $accessor->setValue($viewData, "{$this->direction}WasLimitedByWeight", in_array('weight', $wasLimitedBy));
        }
    }
}