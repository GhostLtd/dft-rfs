<?php

namespace App\DTO\International;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;

class LoadingWithoutUnloading
{
    public function __construct(
        protected Vehicle $vehicle,
        protected Trip $trip,
        protected Action $action,
        protected int $weightLoaded,
        protected int $weightUnloaded
    ) {}

    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function getTrip(): Trip
    {
        return $this->trip;
    }

    public function getAction(): Action
    {
        return $this->action;
    }

    public function getWeightLoaded(): int
    {
        return $this->weightLoaded;
    }

    public function getWeightUnloaded(): int
    {
        return $this->weightUnloaded;
    }
}
