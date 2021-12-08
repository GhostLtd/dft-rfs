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
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
                        // TODO: Do we care about loadings with no corresponding unloading?
                        //       There don't seem to be any such examples in the sample output data
                    }
                    continue;
                }

                $this->normalized[] = $this->getActionData($action, $numberOffset, $rowNumber);
                $rowNumber++;
            }

            if ($this->getCountry($lastAction) !== 'GB') {
                $this->normalized[] = $this->getReturnEmptyData($lastAction, $numberOffset, $rowNumber);
            }
        }

        return $this->normalized;
    }

    protected function getActionData(Action $action, int $numberOffset, int $rowNumber): array
    {
        $trip = $action->getTrip();
        $loadingAction = $action->getLoadingAction();

        $hazardousGoodsCode = $loadingAction->getHazardousGoodsCode();

        return [
            'IHRConsignmentDetailsID' => $action->getId(),
            'IHRVehicleDetailsID' => $trip->getId(),
            'MOA' => $loadingAction->getCargoTypeCode(),
            'TypeOfGoods' => $loadingAction->getGoodsDescriptionNormalized(),
            'DangerousGoods' => $hazardousGoodsCode === '0' ? null : $hazardousGoodsCode,
            'WeightOfGoodsCarriedKG' => $action->getWeightUnloadedAll() ? $loadingAction->getWeightOfGoods() : $action->getWeightOfGoods(),
            'OriginPlace' => $loadingAction->getName(),
            'OriginCountry' => $this->getCountry($loadingAction),
            'LoadedOrder' => $loadingAction->getNumber() + $numberOffset,
            'DestinationPlace' => $action->getName(),
            'DestinationCountry' => $this->getCountry($action),
            'UnloadedOrder' => $action->getNumber() + $numberOffset,
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
            'IHRConsignmentDetailsID' => $this->getGuid(),
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
            'IHRConsignmentDetailsID' => $this->getGuid(),
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

    protected function getGuid(): string
    {
        $connection = $this->entityManager->getConnection();
        $expression = $connection->getDriver()->getDatabasePlatform()->getGuidExpression();
        return $connection->executeQuery("SELECT {$expression} as guid")->fetchOne();
    }

    protected function normalizeName(string $name): string
    {
        return str_replace(' ', '', strtoupper($name));
    }
}