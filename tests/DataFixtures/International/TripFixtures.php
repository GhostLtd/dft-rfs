<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\CargoType;
use App\Entity\Distance;
use App\Entity\HazardousGoods;
use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TripFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->getReference('vehicle:1', Vehicle::class);

        $distance = (new Distance())
            ->setUnit(Distance::UNIT_MILES)
            ->setValue('1450.0');

        $trip = (new Trip())
            ->setVehicle($vehicle)
            ->setOutboundDate(new \DateTime('2021-05-01')) // N.B. These correlate with the dates in SurveyFixtures
            ->setReturnDate(new \DateTime('2021-05-03'))
            ->setIsChangedBodyType(false)
            ->setIsSwappedTrailer(false)
            ->setOutboundUkPort('Dover')
            ->setOutboundForeignPort('Calais')
            ->setReturnForeignPort('Calais')
            ->setReturnUkPort('Dover')
            ->setRoundTripDistance($distance)
            ->setCountriesTransitted(["FR","BE","NL","DE"])
            ->setReturnWasEmpty(false)
            ->setReturnWasLimitedBySpace(false)
            ->setReturnWasLimitedByWeight(false)
            ->setOutboundWasEmpty(false)
            ->setOutboundWasLimitedBySpace(false)
            ->setOutboundWasLimitedByWeight(false)
            ->setOrigin('Chichester')
            ->setDestination('Chichester');

        $vehicle->addTrip($trip);
        $manager->persist($trip);
        $this->setReference('trip:1', $trip);

        $loading = (new Action())
            ->setLoading(true)
            ->setCountry('GB')
            ->setName('Chichester')
            ->setGoodsDescription('other-goods')
            ->setGoodsDescriptionOther('Waste Chemicals')
            ->setWeightOfGoods(23000)
            ->setHazardousGoodsCode(HazardousGoods::CODE_8_CORROSIVE_SUBSTANCES)
            ->setCargoTypeCode(CargoType::CODE_LB_LIQUID_BULK);

        $trip->addAction($loading);
        $manager->persist($loading);

        $unloading = (new Action())
            ->setLoading(false)
            ->setCountry('DE')
            ->setName('Duisburg')
            ->setWeightUnloadedAll(true)
            ->setLoadingAction($loading);

        $trip->addAction($unloading);
        $manager->persist($unloading);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [VehicleFixtures::class];
    }
}