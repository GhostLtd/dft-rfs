<?php

namespace App\Tests\DataFixtures\International\TripDistanceDisabled;

use App\Entity\International\Vehicle;
use App\Tests\DataFixtures\International\VehicleRigidFixtures as BaseVehicleRigidFixtures;
use Doctrine\Persistence\ObjectManager;

class VehicleRigidFixtures extends BaseVehicleRigidFixtures
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