<?php

namespace App\Tests\DataFixtures;

use Doctrine\Persistence\ObjectManager;

class DaySummaryFixtures extends AbstractDaySummaryFixture
{
    public function load(ObjectManager $manager)
    {
        $this->addDaySummary($manager, 2);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [VehicleFixtures::class];
    }
}