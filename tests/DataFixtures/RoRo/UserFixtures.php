<?php

namespace App\Tests\DataFixtures\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\RoRoUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $operator = $this->getReference('roro:operator:1', Operator::class);

        assert($operator instanceof Operator);

        $user = (new RoRoUser())
            ->setUsername('test@example.com')
            ->setOperator($operator);

        $this->setReference('roro:user:1', $user);
        $manager->persist($user);
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            OperatorFixtures::class,
        ];
    }
}