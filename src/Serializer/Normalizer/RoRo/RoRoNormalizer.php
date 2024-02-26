<?php

namespace App\Serializer\Normalizer\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\RoRo\VehicleCount;
use App\Entity\SurveyStateInterface;
use App\Form\RoRo\IntroductionType;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class RoRoNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    public const CONTEXT_KEY = 'for-export';
    public const TOTAL_POWERED_VEHICLES = 'TotalPoweredVehicles';
    public const TOTAL_VEHICLES = 'TotalVehicles';

    use NormalizerAwareTrait;

    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof Survey && ($context[self::CONTEXT_KEY] ?? false) === true;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Survey::class => false,
        ];
    }

    /**
     * @param Survey $object
     * @return array
     */
    #[\Override]
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
            'SurveyState' => $object->getState(),
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
            $countries[$vehicleCount->getCountryCode()] = $isActive ?
                ($vehicleCount->getVehicleCount() ?? 0) :
                null;
        }

        $totals = [];
        foreach($extraMapping as $code => $columnName) {
            $totals[$columnName] = match($code) {
                self::TOTAL_POWERED_VEHICLES => $totalPoweredVehicles,
                self::TOTAL_VEHICLES => $totalVehicles,
                default => $isActive ? ($object->getVehicleCountByOtherCode($code)->getVehicleCount() ?? 0) : null,
            };
        }

        // Just rewrite this so that it comes out as NULL, manual or bulk (a term which will be more familiar with the
        // team, than the internal-representation of "advanced")
        $dataEntryMethod = $object->getDataEntryMethod();

        if ($dataEntryMethod === IntroductionType::DATA_ENTRY_ADVANCED_CHOICE) {
            $dataEntryMethod = 'bulk';
        }

        $tail = [
            'UKPort' => $ukCode,
            'ForeignPort' => $foreignCode,
            'Comments' => $object->getComments(),
            'IsApproved' => $object->getState() === SurveyStateInterface::STATE_APPROVED ? 1 : 0,
            'DataEntryMethod' => $dataEntryMethod,
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
