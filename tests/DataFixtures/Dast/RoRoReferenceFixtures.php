<?php

namespace App\Tests\DataFixtures\Dast;

use App\Entity\Route\ForeignPort;
use App\Entity\Route\Route;
use App\Entity\Route\UkPort;
use App\Tests\DataFixtures\RoRo\CountryFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * The reference data the DAST seeder (App\Utility\Dev\DastSeeder) finds already in place on the DAST stack, where the
 * migrations insert it: the RoRo ports and routes it looks up by name (plus, via CountryFixtures, the countries
 * SurveyCreationHelper reads). The SQLite test database has none of it.
 */
class RoRoReferenceFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // Names and codes as in migrations/Version20230221120438.php
        $dover = (new UkPort())->setName('Dover')->setCode(14);
        $harwich = (new UkPort())->setName('Harwich')->setCode(19);
        $calais = (new ForeignPort())->setName('Calais')->setCode(8);
        $dunkirk = (new ForeignPort())->setName('Dunkirk')->setCode(16);
        $cuxhaven = (new ForeignPort())->setName('Cuxhaven')->setCode(13);

        foreach ([$dover, $harwich, $calais, $dunkirk, $cuxhaven] as $port) {
            $manager->persist($port);
        }

        foreach ([[$dover, $calais], [$dover, $dunkirk], [$harwich, $cuxhaven]] as [$ukPort, $foreignPort]) {
            $manager->persist((new Route())->setUkPort($ukPort)->setForeignPort($foreignPort)->setIsActive(true));
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [CountryFixtures::class];
    }
}
