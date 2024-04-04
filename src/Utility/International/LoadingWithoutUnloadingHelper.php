<?php

namespace App\Utility\International;

use App\DTO\International\LoadingWithoutUnloading;
use App\Entity\International\Action;
use App\Entity\International\Survey;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use Doctrine\Common\Util\ClassUtils;

class LoadingWithoutUnloadingHelper
{
    public function hasLoadingWithoutUnloading(Survey|Vehicle|Trip $target): bool
    {
        $class = ClassUtils::getRealClass($target::class);
        $gen = match($class) {
            Survey::class => $this->getLoadingWithoutUnloadingForSurvey($target),
            Trip::class => $this->getLoadingWithoutUnloadingForTrip($target),
            Vehicle::class => $this->getLoadingWithoutUnloadingForVehicle($target),
        };

        return $gen->current() !== null;
    }

    /**
     * @return \Generator<LoadingWithoutUnloading>
     */
    public function getLoadingWithoutUnloadingForSurvey(Survey $survey): \Generator
    {
        $vehicles = $survey->getResponse()?->getVehicles() ?? [];

        foreach ($vehicles as $vehicle) {
            yield from $this->getLoadingWithoutUnloadingForVehicle($vehicle);
        }
    }

    /**
     * @return \Generator<LoadingWithoutUnloading>
     */
    public function getLoadingWithoutUnloadingForVehicle(Vehicle $vehicle): \Generator
    {
        foreach($vehicle->getTrips() as $trip) {
            yield from $this->getLoadingWithoutUnloadingForTrip($trip);
        }
    }

    /**
     * @return \Generator<LoadingWithoutUnloading>
     */
    public function getLoadingWithoutUnloadingForTrip(Trip $trip): \Generator
    {
        $loadingActions = $trip->getActions()->filter(fn(Action $a) => $a->getLoading());

        foreach ($loadingActions as $loadingAction) {
            $unloadingActions = $loadingAction->getUnloadingActions();

            $weightLoaded = $loadingAction->getWeightOfGoods();

            if ($unloadingActions->isEmpty()) {
                yield new LoadingWithoutUnloading($trip->getVehicle(), $trip, $loadingAction, $weightLoaded, 0);
            } else {
                if ($unloadingActions->count() === 1) {
                    $unloadingAction = $unloadingActions->first();

                    // They unloaded it all, so this isn't an occurrence (of loading-without-unloading)
                    if ($unloadingAction instanceof Action && $unloadingAction->getWeightUnloadedAll()) {
                        continue;
                    }
                }

                $weightUnloaded = $unloadingActions->reduce(function (?int $value, Action $action): int {
                    return ($value ?? 0) + ($action->getWeightOfGoods() ?? 0);
                });

                // If weight unloaded is more than 10% under the weight loaded, flag it as a loading-without-unloading
                $weightThreshold = 0.9 * $weightLoaded;

                if ($weightUnloaded < $weightThreshold) {
                    yield new LoadingWithoutUnloading($trip->getVehicle(), $trip, $loadingAction, $weightLoaded, $weightUnloaded);
                }
            }
        }
    }
}
