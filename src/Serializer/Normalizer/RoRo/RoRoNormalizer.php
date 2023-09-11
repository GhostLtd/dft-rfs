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

        $mapping = $this->getCodeToColumnNameMapping();

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

        $middle = [];
        foreach($mapping as $code => $columnName) {
            $middle[$columnName] = match($code) {
                self::TOTAL_POWERED_VEHICLES => $totalPoweredVehicles,
                self::TOTAL_VEHICLES => $totalVehicles,
                default => $object->getVehicleCountByCode($code)->getVehicleCount() ?? 0,
            };
        }

        $tail = [
            'UKPort' => $route->getUkPort()->getCode(),
            'ForeignPort' => $route->getForeignPort()->getCode(),
        ];

        return array_merge($head, $middle, $tail);
    }

    protected function getCodeToColumnNameMapping(): array
    {
        // This defines the names of the output columns, and which piece of data to put into them
        return [
            'AL' => 'Albania',
            'AT' => 'Austria',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BA' => 'Bosnia',
            'BG' => 'Bulgaria',
            'HR' => 'Croatia',
            'CY' => 'Cyprus',
            'CZ' => 'CzechRepublic',
            'DK' => 'Denmark',
            'IE' => 'Eire',
            'EE' => 'Estonia',
            'FI' => 'Finland',
            'FR' => 'France',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GR' => 'Greece',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IT' => 'Italy',
            'XK' => 'Kosovo',
            'LV' => 'Latvia',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MK' => 'FYR of Macedonia',
            'MT' => 'Malta',
            'MD' => 'Moldova',
            'ME' => 'Montenegro',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            VehicleCount::OTHER_CODE_OTHER => 'OtherCountries',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'RS' => 'Serbia',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'ES' => 'Spain',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'TR' => 'Turkey',
            'UA' => 'Ukraine',
            'GB' => 'UK',
            VehicleCount::OTHER_CODE_UNKNOWN => 'Unknown',
            self::TOTAL_POWERED_VEHICLES => 'TotalPoweredVehicles',
            VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 'UnaccompaniedTrailers',
            self::TOTAL_VEHICLES => 'TotalVehicles',
        ];
    }
}