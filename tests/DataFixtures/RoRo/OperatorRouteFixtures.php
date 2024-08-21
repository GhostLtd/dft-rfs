<?php

namespace App\Tests\DataFixtures\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\Route\Route;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OperatorRouteFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Operator $operator */
        $operator = $this->getReference('roro:operator:1', Operator::class);

        $routeOne = $this->getReference('roro:route:1', Route::class);
        $routeThree = $this->getReference('roro:route:3', Route::class);
        $routeFour = $this->getReference('roro:route:4', Route::class);

        $operator
            ->addRoute($routeOne)
            ->addRoute($routeThree)
            ->addRoute($routeFour);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            OperatorFixtures::class,
            RouteAndPortFixtures::class
        ];
    }
}