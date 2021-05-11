<?php

namespace App\Tests\DataFixtures;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Entity\Vehicle as VehicleBase;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var SurveyResponse $response */
        $response = $this->getReference('response:simple');

        $vehicle = (new Vehicle())
            ->setRegistrationMark('AB01 ABC')
            ->setAxleConfiguration(232)
            ->setTrailerConfiguration(300)
            ->setBodyType(VehicleBase::BODY_TYPE_CURTAIN_SIDED)
            ->setGrossWeight(34000)
            ->setCarryingCapacity(26000)
            ->setOperationType(VehicleBase::OPERATION_TYPE_ON_OWN_ACCOUNT)
            ->setResponse($response);

        $manager->persist($vehicle);
        $this->setReference('vehicle:simple', $vehicle);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [ResponseFixtures::class];
    }
}