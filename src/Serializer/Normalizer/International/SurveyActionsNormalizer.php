<?php

namespace App\Serializer\Normalizer\International;

use App\Entity\International\Action;
use App\Entity\International\Survey;
use App\Form\CountryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Intl\Countries;

class SurveyActionsNormalizer
{
    protected array $normalized;
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function normalize(array $surveys): array
    {
        $this->normalized = [];

        foreach($surveys as $survey) {
            $this->normalizeSurvey($survey);
        }

        return $this->normalized;
    }

    protected function normalizeSurvey(Survey $survey): void
    {
        $response = $survey->getResponse();

        foreach($response->getVehicles() as $vehicle)
        {
            $rowNumber = 1;
            foreach($vehicle->getTrips() as $trip) {
                $actions = $trip->getActions();

                if ($actions->count() === 0) {
                    continue;
                }

                $firstAction = $actions->first();
                $lastAction = $actions->last();

                if ($this->normalizeName($trip->getOrigin()) !== $this->normalizeName($firstAction->getName())) {
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

                if ($this->normalizeName($trip->getDestination()) !== $this->normalizeName($lastAction->getName())) {
                    $this->normalized[] = $this->getReturnEmptyData($lastAction, $numberOffset, $rowNumber);
                }
            }
        }
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
            'Origin' => $this->getPlaceName($loadingAction),
            'LoadedOrder' => $loadingAction->getNumber() + $numberOffset,
            'Destination' => $this->getPlaceName($action),
            'UnloadedOrder' => $action->getNumber() + $numberOffset,
            'RowNumber' => $rowNumber,
            // DateInput - ??
            // MOACode - ??
        ];
    }

    protected function getPlaceName(Action $action)
    {
        $name = $action->getName();
        $country = $action->getCountry();
        $countryOther = $action->getCountryOther();

        if ($countryOther !== null) {
            // Resolve countryOther to a code, if possible
            $countryOtherCode = array_search($countryOther, Countries::getNames());

            if ($countryOtherCode !== false) {
                $countryOther = $countryOtherCode;
            }
        }

        return $country === CountryType::OTHER ?
            "{$name}, {$countryOther}" :
            "{$name}, {$country}";
    }

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
            'Origin' => $trip->getOrigin().", GB",
            'LoadedOrder' => 1,
            'Destination' => $this->getPlaceName($firstAction),
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
            'Origin' => $this->getPlaceName($lastAction),
            'LoadedOrder' => $lastAction->getNumber() + $numberOffset + 1,
            'Destination' => $trip->getDestination().", GB",
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

    protected function normalizeName(string $name)
    {
        return str_replace(' ', '', strtoupper($name));
    }
}