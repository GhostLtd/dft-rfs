<?php

namespace App\Tests\DataFixtures;

use App\Entity\Domestic\Vehicle;
use App\Entity\Vehicle as VehicleBase;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->getReference('vehicle:simple');

        $vehicle
            ->setAxleConfiguration(232)
            ->setTrailerConfiguration(300)
            ->setBodyType(VehicleBase::BODY_TYPE_CURTAIN_SIDED)
            ->setGrossWeight(34000)
            ->setCarryingCapacity(26000)
            ->setOperationType(VehicleBase::OPERATION_TYPE_ON_OWN_ACCOUNT);

        $manager->persist($vehicle);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [BusinessFixtures::class];
    }
}