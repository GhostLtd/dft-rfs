<?php

namespace App\Tests\DataFixtures;

use Doctrine\Persistence\ObjectManager;

class DayFullOfSummaryFixtures extends AbstractDaySummaryFixture
{
    public function load(ObjectManager $manager)
    {
        for($dayNumber=1; $dayNumber<=7; $dayNumber++) {
            $this->addDaySummary($manager, $dayNumber);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [VehicleFixtures::class];
    }
}