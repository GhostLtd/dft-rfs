<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ActionFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Trip $trip */
        $trip = $this->getReference('trip:1', Trip::class);

        /** @var Action $loading */
        $loading = $this->getReference('action:loading:1', Action::class);

        $unloading = (new Action())
            ->setLoading(false)
            ->setCountry('DE')
            ->setName('Duisburg')
            ->setWeightUnloadedAll(true)
            ->setLoadingAction($loading);

        $this->setReference('action:unloading:1', $unloading);

        $trip->addAction($unloading);
        $manager->persist($unloading);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadingActionFixtures::class];
    }
}
