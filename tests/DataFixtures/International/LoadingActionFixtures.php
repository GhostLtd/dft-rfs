<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\CargoType;
use App\Entity\HazardousGoods;
use App\Entity\International\Action;
use App\Entity\International\Trip;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadingActionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Trip $trip */
        $trip = $this->getReference('trip:1');

        $loading = (new Action())
            ->setLoading(true)
            ->setCountry('GB')
            ->setName('Chichester')
            ->setGoodsDescription('other-goods')
            ->setGoodsDescriptionOther('Waste Chemicals')
            ->setWeightOfGoods(23000)
            ->setHazardousGoodsCode(HazardousGoods::CODE_8_CORROSIVE_SUBSTANCES)
            ->setCargoTypeCode(CargoType::CODE_LB_LIQUID_BULK);

        $this->setReference('action:loading:1', $loading);

        $trip->addAction($loading);
        $manager->persist($loading);;

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [TripFixtures::class];
    }
}