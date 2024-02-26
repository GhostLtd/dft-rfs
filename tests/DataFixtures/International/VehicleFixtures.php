<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\Entity\Vehicle as VehicleLookup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var SurveyResponse $response */
        $response = $this->getReference('response:int', SurveyResponse::class);

        $vehicle = (new Vehicle())
            ->setRegistrationMark('AA01 ABC')
            ->setAxleConfiguration(333)
            ->setTrailerConfiguration(300)
            ->setBodyType(VehicleLookup::BODY_TYPE_CURTAIN_SIDED)
            ->setCarryingCapacity(35000)
            ->setGrossWeight(28000)
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
