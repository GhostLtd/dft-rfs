<?php

namespace App\Form\InternationalSurvey\Trip\DataMapper;

use App\Entity\International\Trip;
use App\Entity\Route\Route;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PortsAndCargoStateDataMapper implements DataMapperInterface
{
    public function __construct(protected string $direction, protected array $ports)
    {
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
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

        $ukPort = $accessor->getValue($viewData, "{$this->direction}UkPort");
        $foreignPort = $accessor->getValue($viewData, "{$this->direction}ForeignPort");

        $matchingPair = array_filter($this->ports, fn(Route $route) => $route->getUkPort()->getName() === $ukPort && $route->getForeignPort()->getName() === $foreignPort);

        if (count($matchingPair) === 1) {
            $forms['ports']->setData(array_shift($matchingPair)->getId());
        }

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

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $ports = $forms['ports']->getData();
        $ukPort = null;
        $foreignPort = null;

        if ($ports) {
            $matchingPair = array_filter($this->ports, fn(Route $r) => $r->getId() === $ports);

            if (count($matchingPair) === 1) {
                $matchingPair = array_shift($matchingPair);
                $ukPort = $matchingPair->getUkPort()->getName();
                $foreignPort = $matchingPair->getForeignPort()->getName();
            }
        }

        $accessor->setValue($viewData, "{$this->direction}UkPort", $ukPort);
        $accessor->setValue($viewData, "{$this->direction}ForeignPort", $foreignPort);

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
