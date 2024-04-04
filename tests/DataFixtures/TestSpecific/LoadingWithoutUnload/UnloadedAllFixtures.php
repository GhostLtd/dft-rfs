<?php

namespace App\Tests\DataFixtures\TestSpecific\LoadingWithoutUnload;

use Doctrine\Persistence\ObjectManager;

class UnloadedAllFixtures extends AbstractLoadingWithoutUnloadFixtures
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->doLoad($manager, true);
    }
}
