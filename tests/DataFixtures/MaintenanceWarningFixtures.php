<?php

namespace App\Tests\DataFixtures;

use App\Entity\Utility\MaintenanceWarning;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MaintenanceWarningFixtures extends Fixture implements FixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // Start within the +1 week warning window so getNotificationBanner() returns it.
        $start = (new \DateTime())->modify('+1 day');
        $end = (clone $start)->modify('+2 hours');

        $maintenanceWarning = (new MaintenanceWarning())
            ->setStart($start)
            ->setEnd($end);

        $manager->persist($maintenanceWarning);
        $this->setReference('maintenance-warning', $maintenanceWarning);
        $manager->flush();
    }
}
