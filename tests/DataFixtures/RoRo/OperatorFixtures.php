<?php

namespace App\Tests\DataFixtures\RoRo;

use App\Entity\RoRo\Operator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OperatorFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $operator = (new Operator())
            ->setName('Test operator')
            ->setCode(1011)
            ->setIsActive(true);

        $manager->persist($operator);

        $this->setReference('roro:operator:1', $operator);

        $manager->flush();
    }
}