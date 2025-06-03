<?php

namespace App\Tests\DataFixtures\Domestic;

use Doctrine\Persistence\ObjectManager;

class DaySummaryFixtures extends AbstractDaySummaryFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->addDaySummary($manager, 2);
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [VehicleFixtures::class];
    }
}