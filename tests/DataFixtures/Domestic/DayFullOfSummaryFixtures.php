<?php

namespace App\Tests\DataFixtures\Domestic;

use Doctrine\Persistence\ObjectManager;

class DayFullOfSummaryFixtures extends AbstractDaySummaryFixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        for($dayNumber=1; $dayNumber<=7; $dayNumber++) {
            $this->addDaySummary($manager, $dayNumber);
        }
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [VehicleFixtures::class];
    }
}