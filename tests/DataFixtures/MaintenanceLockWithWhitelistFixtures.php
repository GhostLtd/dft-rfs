<?php

namespace App\Tests\DataFixtures;

use App\Entity\Utility\MaintenanceLock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MaintenanceLockWithWhitelistFixtures extends Fixture implements FixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $maintenanceLock = (new MaintenanceLock())
            ->setWhitelistedIps(['127.0.0.1']);

        $manager->persist($maintenanceLock);
        $this->setReference('maintenance-lock', $maintenanceLock);
        $manager->flush();
    }
}