<?php

namespace App\Form\InternationalSurvey\Trip\DataMapper;

use App\Entity\International\Trip;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PortsAndCargoStateDataMapper implements DataMapperInterface
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

        $forms["{$this->direction}UkPort"]->setData($accessor->getValue($viewData, "{$this->direction}UkPort"));
        $forms["{$this->direction}ForeignPort"]->setData($accessor->getValue($viewData, "{$this->direction}ForeignPort"));

        $wasEmpty = $accessor->getValue($viewData, "{$this->direction}WasEmpty");
        $wasLimitedBy = [];

        if ($limitedBySpace = $accessor->getValue($viewData, "{$this->direction}WasLimitedBySpace")) {
            $wasLimitedBy[] = 'space';
        }

        if ($limitedByWeight = $accessor->getValue($viewData, "{$this->direction}WasLimitedByWeight")) {
            $wasLimitedBy[] = 'weight';
        }

        $isAtCapacity = is_null($limitedBySpace) && is_null($limitedByWeight) ? null : count($wasLimitedBy) > 0;

        if ($isAtCapacity) {
            $forms['wasLimitedBy']->setData($wasLimitedBy);
        } else {
            $forms['wasEmpty']->setData($wasEmpty);
        }

        $forms['wasAtCapacity']->setData($isAtCapacity);
    }

    public function mapFormsToData($forms, &$viewData)
    {
        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $accessor->setValue($viewData, "{$this->direction}UkPort", $forms["{$this->direction}UkPort"]->getData());
        $accessor->setValue($viewData, "{$this->direction}ForeignPort", $forms["{$this->direction}ForeignPort"]->getData());

        $wasAtCapacity = $forms['wasAtCapacity']->getData();

        if ($wasAtCapacity === false) {
            $wasEmpty = $forms['wasEmpty']->getData();

            $isValid = $wasEmpty !== null;

            $accessor->setValue($viewData, "{$this->direction}WasEmpty", $isValid ? $wasEmpty : null);
            $accessor->setValue($viewData, "{$this->direction}WasLimitedBySpace", $isValid ? false : null);
            $accessor->setValue($viewData, "{$this->direction}WasLimitedByWeight", $isValid ? false : null);
        } elseif ($wasAtCapacity === true) {
            $wasLimitedBy = $forms['wasLimitedBy']->getData();
            $wasLimitedBySpace = in_array('space', $wasLimitedBy);
            $wasLimitedByWeight = in_array('weight', $wasLimitedBy);
            $isValid = $wasLimitedBySpace || $wasLimitedByWeight;

            $accessor->setValue($viewData, "{$this->direction}WasEmpty", $isValid ? false : null);
            $accessor->setValue($viewData, "{$this->direction}WasLimitedBySpace", $isValid ? $wasLimitedBySpace : null);
            $accessor->setValue($viewData, "{$this->direction}WasLimitedByWeight", $isValid ? $wasLimitedByWeight : null);
        }
    }
}