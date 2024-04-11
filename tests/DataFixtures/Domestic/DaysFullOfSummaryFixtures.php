<?php

namespace App\Tests\DataFixtures\Domestic;

use Doctrine\Persistence\ObjectManager;

class DaysFullOfSummaryFixtures extends AbstractDaySummaryFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
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
