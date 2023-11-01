<?php

namespace App\Serializer\Normalizer\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\RoRo\VehicleCount;
use App\Entity\SurveyStateInterface;
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

        $twoDigitPadded = fn(int $digits): string => str_pad(strval($digits), 2, "0",STR_PAD_LEFT);

        $route = $object->getRoute();

        $ukCode = $twoDigitPadded($route->getUkPort()->getCode());
        $foreignCode = $twoDigitPadded($route->getForeignPort()->getCode());
        $operatorCode = $twoDigitPadded($object->getOperator()->getCode());

        $extraMapping = $this->getExtraColumnNameMapping();

        $isActive = $object->getIsActiveForPeriod();

        $head = [
            'Year' => intval($surveyStart->format('Y')),
            'Quarter' => $quarter,
            'Month' => $month,
            'RoRoNo' => $ukCode.$foreignCode.$operatorCode,
            'OperatorId' => $operatorCode,
            'IsActiveForMonth' => $isActive ? 1 : 0,
        ];

        if ($isActive) {
            [
                'total_powered' => $totalPoweredVehicles,
                'total' => $totalVehicles,
            ] = $object->getTotalVehicleCounts();
        } else {
            $totalPoweredVehicles = null;
            $totalVehicles = null;
        }

        $countries = [];
        foreach($object->getCountryVehicleCounts() as $vehicleCount) {
            $countries[$vehicleCount->getCountryCode()] = $vehicleCount->getVehicleCount();
        }

        $totals = [];
        foreach($extraMapping as $code => $columnName) {
            $totals[$columnName] = match($code) {
                self::TOTAL_POWERED_VEHICLES => $totalPoweredVehicles,
                self::TOTAL_VEHICLES => $totalVehicles,
                default => $object->getVehicleCountByOtherCode($code)->getVehicleCount(),
            };
        }

        $tail = [
            'UKPort' => $ukCode,
            'ForeignPort' => $foreignCode,
            'Comments' => $object->getComments(),
            'IsApproved' => $object->getState() === SurveyStateInterface::STATE_APPROVED ? 1 : 0,
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