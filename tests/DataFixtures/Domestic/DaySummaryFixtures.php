<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Tests\DataFixtures\Domestic\AbstractDaySummaryFixture;
use App\Tests\DataFixtures\Domestic\VehicleFixtures;
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