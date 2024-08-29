<?php

namespace App\Tests\DataFixtures\International\TripDistanceDisabled;

use App\Entity\International\Vehicle;
use App\Tests\DataFixtures\International\VehicleFixtures as BaseVehicleFixtures;
use Doctrine\Persistence\ObjectManager;

class VehicleFixtures extends BaseVehicleFixtures
{
    use DistanceDisabledSurveyTrait;
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        /** @var Vehicle $vehicle */
        $vehicle = $this->getReference('vehicle:1', Vehicle::class);
        $this->updateSurveyDates($manager, $vehicle->getSurveyResponse()->getSurvey());
    }
}
