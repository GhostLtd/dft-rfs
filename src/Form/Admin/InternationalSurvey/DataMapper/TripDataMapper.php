<?php

namespace App\Form\Admin\InternationalSurvey\DataMapper;

use App\Entity\International\Trip;
use App\Entity\Route\Route;
use App\Form\Admin\InternationalSurvey\TripType;
use App\Utility\PortsDataSet;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class TripDataMapper implements DataMapperInterface
{
    public function __construct(
        /** @var array<PortsDataSet> $portDataSets */
        protected array $portsDataSets
    ) {}

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

            $forms["{$direction}Date"]->setData($accessor->getValue($viewData, "{$direction}Date"));

            $ukPort = $accessor->getValue($viewData, "{$direction}UkPort");
            $foreignPort = $accessor->getValue($viewData, "{$direction}ForeignPort");

            $ports = $this->portsDataSets[$direction]->getPorts();

            $matchingPair = array_filter($ports, fn(Route $route) => $route->getUkPort()->getName() === $ukPort && $route->getForeignPort()->getName() === $foreignPort);

            if (count($matchingPair) === 1) {
                $forms["{$direction}Ports"]->setData(array_shift($matchingPair)->getId());
            }
        }

        $forms['countriesTransitted']->setData($viewData->getCountriesTransitted());
        $forms['countriesTransittedOther']->setData($viewData->getCountriesTransittedOther());
        $forms['roundTripDistance']->setData($viewData->getRoundTripDistance());
        $forms['origin']->setData($viewData->getOrigin());
        $forms['destination']->setData($viewData->getDestination());

        $forms['axle_config']->setData($viewData->getIsSwappedTrailer() ? $viewData->getAxleConfiguration() : false);
        if ($forms['body_type'] ?? false) {
            $forms['body_type']->setData($viewData->getIsChangedBodyType() ? $viewData->getBodyType() : false);
        }
        $forms['gross_weight']->setData($viewData->getGrossWeight());
        $forms['carrying_capacity']->setData($viewData->getCarryingCapacity());
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

        foreach(['outbound', 'return'] as $direction) {
            $cargoState = $forms["{$direction}CargoState"]->getData();
            $limitedByBoth = $cargoState === TripType::CARGO_STATE_CAPACITY_BOTH;

            $accessor->setValue($viewData, "{$direction}WasEmpty", $cargoState === TripType::CARGO_STATE_EMPTY);
            $accessor->setValue($viewData, "{$direction}WasLimitedBySpace", $limitedByBoth || $cargoState === TripType::CARGO_STATE_CAPACITY_SPACE);
            $accessor->setValue($viewData, "{$direction}WasLimitedByWeight", $limitedByBoth || $cargoState === TripType::CARGO_STATE_CAPACITY_WEIGHT);
            $accessor->setValue($viewData, "{$direction}Date", $forms["{$direction}Date"]->getData());

            $ports = $forms["{$direction}Ports"]->getData();
            $ukPort = null;
            $foreignPort = null;

            if ($ports) {
                $matchingPair = array_filter($this->portsDataSets[$direction]->getPorts(), fn(Route $r) => $r->getId() === $ports);

                if (count($matchingPair) === 1) {
                    $matchingPair = array_shift($matchingPair);
                    $ukPort = $matchingPair->getUkPort()->getName();
                    $foreignPort = $matchingPair->getForeignPort()->getName();
                }
            }

            $accessor->setValue($viewData, "{$direction}UkPort", $ukPort);
            $accessor->setValue($viewData, "{$direction}ForeignPort", $foreignPort);
        }

        $viewData->setCountriesTransitted($forms['countriesTransitted']->getData());
        $viewData->setCountriesTransittedOther($forms['countriesTransittedOther']->getData());
        $viewData->setRoundTripDistance($forms['roundTripDistance']->getData());
        $viewData->setOrigin($forms['origin']->getData());
        $viewData->setDestination($forms['destination']->getData());

        $axleConfig = $forms['axle_config']->getData();
        $viewData->setIsSwappedTrailer($axleConfig !== null);
        $viewData->setAxleConfiguration($axleConfig ? : null);

        if ($forms['body_type'] ?? false) {
            $viewData->setIsChangedBodyType($forms['body_type']->getData() !== false);
            $viewData->setBodyType($forms['body_type']->getData() ? : null);
        }
        $viewData->setGrossWeight($forms['gross_weight']->getData() ? : null);
        $viewData->setCarryingCapacity($forms['carrying_capacity']->getData() ? : null);
    }
}
