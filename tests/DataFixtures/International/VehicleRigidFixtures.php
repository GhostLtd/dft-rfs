<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\Entity\Vehicle as VehicleLookup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VehicleRigidFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var SurveyResponse $response */
        $response = $this->getReference('response:int', SurveyResponse::class);

        $vehicle = (new Vehicle())
            ->setRegistrationMark('AA01 ABC')
            ->setAxleConfiguration(130)
            ->setTrailerConfiguration(100)
            ->setBodyType(VehicleLookup::BODY_TYPE_CURTAIN_SIDED)
            ->setCarryingCapacity(20000)
            ->setGrossWeight(25000)
            ->setOperationType(VehicleLookup::OPERATION_TYPE_ON_OWN_ACCOUNT);

        $response->addVehicle($vehicle);

        $this->setReference('vehicle:1', $vehicle);

        $manager->persist($vehicle);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [ResponseStillActiveFixtures::class];
    }
}