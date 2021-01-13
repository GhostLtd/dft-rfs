<?php

namespace App\Form\Admin\InternationalSurvey\DataMapper;

use App\Entity\International\Trip;
use App\Form\Admin\InternationalSurvey\TripType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class TripDataMapper implements DataMapperInterface
{
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

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach(['outbound', 'return'] as $direction) {
            if ($accessor->getValue($viewData, "{$direction}WasEmpty")) {
                $cargoState = TripType::CARGO_STATE_EMPTY;
            } else {
                $cargoState = TripType::CARGO_STATE_PART_FILLED;

                if ($accessor->getValue($viewData, "{$direction}WasLimitedBySpace")) {
                    $cargoState = TripType::CARGO_STATE_CAPACITY_SPACE;
                }

                if ($accessor->getValue($viewData, "{$direction}WasLimitedByWeight")) {
                    $cargoState = ($cargoState === TripType::CARGO_STATE_CAPACITY_SPACE) ?
                        TripType::CARGO_STATE_CAPACITY_BOTH :
                        TripType::CARGO_STATE_CAPACITY_WEIGHT;
                }
            }

            $forms["{$direction}CargoState"]->setData($cargoState);

            foreach(['Date', 'UkPort', 'ForeignPort'] as $field) {
                $forms["{$direction}{$field}"]->setData($accessor->getValue($viewData, "{$direction}{$field}"));
            }
        }

        $forms['countriesTransitted']->setData($viewData->getCountriesTransitted());
        $forms['countriesTransittedOther']->setData($viewData->getCountriesTransittedOther());
        $forms['roundTripDistance']->setData($viewData->getRoundTripDistance());
    }

    public function mapFormsToData($forms, &$viewData)
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach(['outbound', 'return'] as $direction) {
            $cargoState = $forms["{$direction}CargoState"]->getData();
            $limitedByBoth = $cargoState === TripType::CARGO_STATE_CAPACITY_BOTH;

            $accessor->setValue($viewData, "{$direction}WasEmpty", $cargoState === TripType::CARGO_STATE_EMPTY);
            $accessor->setValue($viewData, "{$direction}WasLimitedBySpace", $limitedByBoth || $cargoState === TripType::CARGO_STATE_CAPACITY_SPACE);
            $accessor->setValue($viewData, "{$direction}WasLimitedByWeight", $limitedByBoth || $cargoState === TripType::CARGO_STATE_CAPACITY_WEIGHT);

            foreach(['Date', 'UkPort', 'ForeignPort'] as $field) {
                $accessor->setValue($viewData, "{$direction}{$field}", $forms["{$direction}{$field}"]->getData());
            }
        }

        $viewData->setCountriesTransitted($forms['countriesTransitted']->getData());
        $viewData->setCountriesTransittedOther($forms['countriesTransittedOther']->getData());
        $viewData->setRoundTripDistance($forms['roundTripDistance']->getData());
    }
}