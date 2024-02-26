<?php

namespace App\Serializer\Normalizer\International;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Form\CountryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Intl\Countries;

class TripActionsExportNormalizer
{
    protected array $normalized;

    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param Trip[]|array $trips
     */
    public function normalize(array $trips): array
    {
        $this->normalized = [];

        foreach($trips as $trip) {
            $rowNumber = 1;

            $actions = $trip->getActions();

            if ($actions->count() === 0) {
                continue;
            }

            $firstAction = $actions->first();
            $lastAction = $actions->last();

            if ($this->getCountry($firstAction) !== 'GB') {
                $this->normalized[] = $this->getOutboundEmptyData($firstAction, $rowNumber);
                $numberOffset = 2;
                $rowNumber++;
            } else {
                $numberOffset = 0;
            }

            foreach ($trip->getActions() as $action) {
                if ($action->getLoading()) {
                    if ($action->getUnloadingActions()->count() === 0) {
                        $this->normalized[] = $this->getActionData($action, null, $numberOffset, $rowNumber);
                        $rowNumber++;
                    }
                    continue;
                }

                $this->normalized[] = $this->getActionData($action->getLoadingAction(), $action, $numberOffset, $rowNumber);
                $rowNumber++;
            }

            if ($this->getCountry($lastAction) !== 'GB') {
                $this->normalized[] = $this->getReturnEmptyData($lastAction, $numberOffset, $rowNumber);
            }
        }

        return $this->normalized;
    }

    protected function getActionData(Action $loadingAction, ?Action $unloadingAction, int $numberOffset, int $rowNumber): array
    {
        $trip = $loadingAction->getTrip();

        $hazardousGoodsCode = $loadingAction->getHazardousGoodsCode();

        return [
            'IHRConsignmentDetailsID' => $unloadingAction ? $unloadingAction->getId() : $loadingAction->getId(),
            'IHRVehicleDetailsID' => $trip->getId(),
            'MOA' => $loadingAction->getCargoTypeCode(),
            'TypeOfGoods' => $loadingAction->getGoodsDescriptionNormalized(),
            'DangerousGoods' => $hazardousGoodsCode === '0' ? null : $hazardousGoodsCode,
            'WeightOfGoodsCarriedKG' => !$unloadingAction || $unloadingAction->getWeightUnloadedAll()
                ? $loadingAction->getWeightOfGoods()
                : $unloadingAction->getWeightOfGoods(),
            'OriginPlace' => $loadingAction->getName(),
            'OriginCountry' => $this->getCountry($loadingAction),
            'LoadedOrder' => $loadingAction->getNumber() + $numberOffset,
            'DestinationPlace' => $unloadingAction ? $unloadingAction->getName() : null,
            'DestinationCountry' => $unloadingAction ? $this->getCountry($unloadingAction) : null,
            'UnloadedOrder' => $unloadingAction ? $unloadingAction->getNumber() + $numberOffset : null,
            'RowNumber' => $rowNumber,
            // DateInput - ??
            // MOACode - ??
        ];
    }

    protected function getCountry(Action $action): string
    {
        $country = $action->getCountry();
        $countryOther = $action->getCountryOther();

        if ($countryOther !== null) {
            // Resolve countryOther to a code, if possible
            $countryOtherCode = array_search($countryOther, Countries::getNames());

            if ($countryOtherCode !== false) {
                $countryOther = $countryOtherCode;
            }
        }

        return $country === CountryType::OTHER
            ? $countryOther
            : $country;
    }

//    protected function getPlaceName(Action $action): string
//    {
//        $name = $action->getName();
//        $country = $this->getCountry($action);
//        return "$name, $country";
//    }

    protected function getOutboundEmptyData(Action $firstAction, int $rowNumber): array
    {
        $trip = $firstAction->getTrip();

        return [
            'IHRConsignmentDetailsID' => $this->getGuid($trip->getId(), 'outbound'),
            'IHRVehicleDetailsID' => $trip->getId(),
            'MOA' => 'NS',
            'TypeOfGoods' => 'Empty',
            'DangerousGoods' => null,
            'WeightOfGoodsCarriedKG' => 0,
            'OriginPlace' => $trip->getOrigin(),
            'OriginCountry' => "GB",
            'LoadedOrder' => 1,
            'DestinationPlace' => $firstAction->getName(),
            'DestinationCountry' => $this->getCountry($firstAction),
            'UnloadedOrder' => 2,
            'RowNumber' => $rowNumber,
            // DateInput - ??
            // MOACode - ??
        ];
    }

    protected function getReturnEmptyData(Action $lastAction, int $numberOffset, int $rowNumber): array
    {
        $trip = $lastAction->getTrip();

        return [
            'IHRConsignmentDetailsID' => $this->getGuid($trip->getId(), 'return'),
            'IHRVehicleDetailsID' => $trip->getId(),
            'MOA' => 'NS',
            'TypeOfGoods' => 'Empty',
            'DangerousGoods' => null,
            'WeightOfGoodsCarriedKG' => 0,
            'OriginPlace' => $lastAction->getName(),
            'OriginCountry' => $this->getCountry($lastAction),
            'LoadedOrder' => $lastAction->getNumber() + $numberOffset + 1,
            'DestinationPlace' => $trip->getDestination(),
            'DestinationCountry' => "GB",
            'UnloadedOrder' => $lastAction->getNumber() + $numberOffset + 2,
            'RowNumber' => $rowNumber,
            // DateInput - ??
            // MOACode - ??
        ];
    }

    /**
     * We need reproducible UUIDs, so they are the same each time an export is run
     */
    protected function getGuid(string $seedGuid, string $context): string
    {
        $data = md5($context . $seedGuid, true);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    protected function normalizeName(string $name): string
    {
        return str_replace(' ', '', strtoupper($name));
    }
}
