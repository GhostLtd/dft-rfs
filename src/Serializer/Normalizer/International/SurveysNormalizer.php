<?php

namespace App\Serializer\Normalizer\International;

use App\Entity\International\Action;
use App\Entity\International\Survey;

class SurveysNormalizer
{
    protected array $normalized;

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
            foreach($vehicle->getTrips() as $trip) {
                $actions = $trip->getActions();

                if ($actions->count() === 0) {
                    continue;
                }

                $firstAction = $actions->first();
                $lastAction = $actions->last();

                if ($trip->getOrigin() !== $firstAction->getName()) {
                    $this->normalized[] = $this->getOutboundEmptyData($firstAction);
                    $numberOffset = 2;
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

                    $this->normalized[] = $this->getActionData($action, $numberOffset);
                }

                if ($trip->getDestination() !== $lastAction->getName()) {
                    $this->normalized[] = $this->getReturnEmptyData($lastAction, $numberOffset);
                }
            }
        }
    }

    protected function getActionData(Action $action, int $numberOffset): array
    {
        $trip = $action->getTrip();
        $loadingAction = $action->getLoadingAction();

        $hazardousGoodsCode = $loadingAction->getHazardousGoodsCode();

        return [
            'IRHConsignmentDetailsID' => $action->getId(),
            'IRHVehicleDetailsID' => $trip->getId(),
            'MOA' => $loadingAction->getCargoTypeCode(),
            'TypeOfGoods' => $loadingAction->getGoodsDescriptionNormalized(),
            'DangerousGoods' => $hazardousGoodsCode === '0' ? null : $hazardousGoodsCode,
            'WeightOfGoodsCarriedKG' => $action->getWeightUnloadedAll() ? $loadingAction->getWeightOfGoods() : $action->getWeightOfGoods(),
            'Origin' => $loadingAction->getName(), // TODO: Integrate country
            'LoadedOrder' => $loadingAction->getNumber() + $numberOffset,
            'Destination' => $action->getName(), // TODO: Integrate country
            'UnloadedOrder' => $action->getNumber() + $numberOffset,
            // RowNumber ??
            // DateInput - ??
            // MOACode - ??
        ];
    }

    protected function getOutboundEmptyData(Action $firstAction): array
    {
        $trip = $firstAction->getTrip();

        return [
            'IRHConsignmentDetailsID' => $trip->getId().'-initial', // TODO: Fix IDs
            'IRHVehicleDetailsID' => $trip->getId(),
            'MOA' => 'NS',
            'TypeOfGoods' => 'Empty',
            'DangerousGoods' => null,
            'WeightOfGoodsCarriedKG' => 0,
            'Origin' => $trip->getOrigin(),
            'LoadedOrder' => 1,
            'Destination' => $firstAction->getName(),
            'UnloadedOrder' => 2,
            // RowNumber ??
            // DateInput - ??
            // MOACode - ??
        ];
    }

    protected function getReturnEmptyData(Action $lastAction, int $numberOffset): array
    {
        $trip = $lastAction->getTrip();

        return [
            'IRHConsignmentDetailsID' => $trip->getId().'-return', // TODO: Fix IDs
            'IRHVehicleDetailsID' => $trip->getId(),
            'MOA' => 'NS',
            'TypeOfGoods' => 'Empty',
            'DangerousGoods' => null,
            'WeightOfGoodsCarriedKG' => 0,
            'Origin' => $lastAction->getName(),
            'LoadedOrder' => $lastAction->getNumber() + $numberOffset + 1,
            'Destination' => $trip->getDestination(),
            'UnloadedOrder' => $lastAction->getNumber() + $numberOffset + 2,
            // RowNumber ??
            // DateInput - ??
            // MOACode - ??
        ];
    }
}