<?php

namespace App\Serializer\Normalizer\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\RoRo\VehicleCount;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class RoRoNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    const CONTEXT_KEY = 'for-export';
    const TOTAL_POWERED_VEHICLES = 'TotalPoweredVehicles';
    const TOTAL_VEHICLES = 'TotalVehicles';

    use NormalizerAwareTrait;

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof Survey && ($context[self::CONTEXT_KEY] ?? false) === true;
    }

    /**
     * @param Survey $object
     * @return array
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $surveyStart = $object->getSurveyPeriodStart();
        $month = intval($surveyStart->format('m'));

        $quarter = intval(ceil($month / 3));

        $route = $object->getRoute();
        $routeCode = $route->getRouteCode();
        $operatorCode = $object->getOperator()->getCode();

        $extraMapping = $this->getExtraColumnNameMapping();

        $head = [
            'Year' => intval($surveyStart->format('Y')),
            'Quarter' => $quarter,
            'Month' => $month,
            'RoRoNo' => $routeCode.$operatorCode,
            'OperatorId' => $operatorCode,
        ];

        [
            'total_powered' => $totalPoweredVehicles,
            'total' => $totalVehicles,
        ] = $object->getTotalVehicleCounts();

        $countries = [];
        foreach($object->getCountryVehicleCounts() as $vehicleCount) {
            $countries[$vehicleCount->getCountryCode()] = $vehicleCount->getVehicleCount();
        }

        $totals = [];
        foreach($extraMapping as $code => $columnName) {
            $totals[$columnName] = match($code) {
                self::TOTAL_POWERED_VEHICLES => $totalPoweredVehicles,
                self::TOTAL_VEHICLES => $totalVehicles,
                default => $object->getVehicleCountByOtherCode($code)->getVehicleCount() ?? 0,
            };
        }

        $tail = [
            'UKPort' => $route->getUkPort()->getCode(),
            'ForeignPort' => $route->getForeignPort()->getCode(),
        ];

        return array_merge($head, $countries, $totals, $tail);
    }

    protected function getExtraColumnNameMapping(): array
    {
        // This defines the names of the output columns, and which piece of data to put into them
        return [
            VehicleCount::OTHER_CODE_OTHER => 'OtherCountries',
            VehicleCount::OTHER_CODE_UNKNOWN => 'Unknown',
            self::TOTAL_POWERED_VEHICLES => 'TotalPoweredVehicles',
            VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 'UnaccompaniedTrailers',
            self::TOTAL_VEHICLES => 'TotalVehicles',
        ];
    }
}