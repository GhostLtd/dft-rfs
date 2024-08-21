<?php

namespace App\Tests\DataFixtures\TestSpecific\LoadingWithoutUnload;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Tests\DataFixtures\International\LoadingActionFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

abstract class AbstractLoadingWithoutUnloadFixtures extends Fixture implements DependentFixtureInterface
{
    public function doLoad(ObjectManager $manager, bool $unloadedAll, array $weightsOfGoods=[]): void
    {
        if ($unloadedAll && count($weightsOfGoods) > 0) {
            throw new \RuntimeException('Cannot both "unloadAll" and have individual weights');
        }

        /** @var Trip $trip */
        $trip = $this->getReference('trip:1', Trip::class);

        /** @var Action $loading */
        $loading = $this->getReference('action:loading:1', Action::class);

        $createAction = function(Action $loading): Action {
            $unloading = (new Action())
                ->setLoading(false)
                ->setCountry('DE')
                ->setName('Duisburg');

            $loading->addUnloadingAction($unloading);
            return $unloading;
        };

        if ($unloadedAll) {
            $unloading = $createAction($loading)
                ->setWeightUnloadedAll(true);

            $trip->addAction($unloading);
            $manager->persist($unloading);
        } else {
            foreach ($weightsOfGoods as $weightOfGoods) {
                $unloading = $createAction($loading)
                    ->setWeightUnloadedAll(false)
                    ->setWeightOfGoods($weightOfGoods);

                $trip->addAction($unloading);
                $manager->persist($unloading);
            }
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadingActionFixtures::class];
    }
}
