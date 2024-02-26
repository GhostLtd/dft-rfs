<?php

namespace App\Tests\DataFixtures\RoRo;

use App\Entity\Route\ForeignPort;
use App\Entity\Route\Route;
use App\Entity\Route\UkPort;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RouteAndPortFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $ukPortOne = $this->createUkPort($manager, 'Portfoot', 12, 'roro:port:uk:1');
        $ukPortTwo = $this->createUkPort($manager, 'Southester', 27, 'roro:port:uk:2');
        $foreignPortOne = $this->createForeignPort($manager, 'Waspwerp', 12, 'roro:port:foriegn:1');
        $foreignPortTwo = $this->createForeignPort($manager, 'Roysterdam', 34, 'roro:port:foriegn:2');

        $this->createRoute($manager, $ukPortOne, $foreignPortOne, true, 'roro:route:1');
        $this->createRoute($manager, $ukPortOne, $foreignPortTwo, true, 'roro:route:2');
        $this->createRoute($manager, $ukPortTwo, $foreignPortOne, false, 'roro:route:3');
        $this->createRoute($manager, $ukPortTwo, $foreignPortTwo, true, 'roro:route:4');

        $manager->flush();
    }

    protected function createRoute(ObjectManager $manager, UkPort $ukPort, ForeignPort $foreignPort, bool $isActive, string $reference): Route
    {
        $route = (new Route())
            ->setUkPort($ukPort)
            ->setForeignPort($foreignPort)
            ->setIsActive($isActive);

        $this->setReference($reference, $route);
        $manager->persist($route);

        return $route;
    }

    protected function createUkPort(ObjectManager $manager, string $name, int $code, string $reference): UkPort
    {
        return $this->createPort($manager, $name, $code, UkPort::class, $reference);
    }

    protected function createForeignPort(ObjectManager $manager, string $name, int $code, string $reference): ForeignPort
    {
        return $this->createPort($manager, $name, $code, ForeignPort::class, $reference);
    }

    protected function createPort(ObjectManager $manager, string $name, int $code, string $class, string $reference): UkPort|ForeignPort
    {
        $port = (new $class)
            ->setName($name)
            ->setCode($code);

        $this->setReference($reference, $port);
        $manager->persist($port);

        return $port;
    }
}