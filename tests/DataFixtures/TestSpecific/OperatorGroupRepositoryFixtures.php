<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\RoRo\OperatorGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OperatorGroupRepositoryFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $addGroup = function(string $name) use ($manager): void {
            $group = (new OperatorGroup())
                ->setName($name);

            $this->addReference( 'operator-group:' . strtolower($name), $group);
            $manager->persist($group);
        };

        $addGroup('Banana');
        $addGroup('Apple');

        $manager->flush();
    }
}