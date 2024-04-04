<?php

namespace App\Tests\DataFixtures\TestSpecific\LoadingWithoutUnload;

use Doctrine\Persistence\ObjectManager;

class UnloadedNinetyOnePercentInMultipleDropsFixtures extends AbstractLoadingWithoutUnloadFixtures
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->doLoad($manager, false, [8530, 3000, 7000, 2400]);
    }
}
