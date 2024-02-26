<?php

namespace App\Tests\DataFixtures;

use App\Entity\PasscodeUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = (new PasscodeUser())
            ->setUsername('test')
            ->setPlainPassword('test');

        $manager->persist($user);

        $this->setReference('user:frontend', $user);

        $manager->flush();
    }
}