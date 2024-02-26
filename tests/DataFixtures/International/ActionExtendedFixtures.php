<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\CargoType;
use App\Entity\HazardousGoods;
use App\Entity\International\Action;
use App\Entity\International\Trip;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ActionExtendedFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * An extra loading action so that we can better test the unloading flow
     * (i.e. changing from one loading location to another)
     */
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Trip $trip */
        $trip = $this->getReference('trip:1', Trip::class);

        $loading = (new Action())
            ->setLoading(true)
            ->setCountry('GB')
            ->setName('Bognor Regis')
            ->setGoodsDescription('other-goods')
            ->setGoodsDescriptionOther('Bread')
            ->setWeightOfGoods(12000)
            ->setHazardousGoodsCode(null)
            ->setCargoTypeCode(CargoType::CODE_RC_ROLL_CAGES);

        $this->setReference('action:loading:2', $loading);

        $trip->addAction($loading);

        // Swap the numbers so that we have:
        // loading, loading <this one>, unloading

        /** @var Action $unloading */
        $unloading = $this->getReference('action:unloading:1', Action::class);
        $unloading->setNumber(3);

        $loading->setNumber(2);

        $manager->persist($loading);
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [ActionFixtures::class];
    }
}