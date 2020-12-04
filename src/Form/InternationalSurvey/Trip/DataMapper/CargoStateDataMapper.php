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

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (!isset($forms['wasEmpty']) || !isset($forms['wasLimitedBy'])) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $wasEmpty = $accessor->getValue($viewData, "{$this->direction}WasEmpty");
        $wasLimitedBy = [];

        if ($accessor->getValue($viewData, "{$this->direction}WasLimitedBySpace")) {
            $wasLimitedBy[] = 'space';
        }

        if ($accessor->getValue($viewData, "{$this->direction}WasLimitedByWeight")) {
            $wasLimitedBy[] = 'weight';
        }

        $forms['wasEmpty']->setData($wasEmpty);
        $forms['wasLimitedBy']->setData($wasLimitedBy);
    }

    public function mapFormsToData($forms, &$viewData)
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $wasEmpty = $forms['wasEmpty']->getData();
        $accessor->setValue($viewData, "{$this->direction}WasEmpty", $wasEmpty);

        // Naughty, but forcing both wasLimitedBy fields to be false, if wasEmpty
        // (In any case, that part of the form will be hidden, if wasEmpty was chosen)
        $wasLimitedBy = $wasEmpty ?
            [] :
            $forms['wasLimitedBy']->getData();

        $accessor->setValue($viewData, "{$this->direction}WasLimitedBySpace", in_array('space', $wasLimitedBy));
        $accessor->setValue($viewData, "{$this->direction}WasLimitedByWeight", in_array('weight', $wasLimitedBy));
    }
}