<?php

namespace App\Tests\DataFixtures\Rehearsal;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
use App\Tests\DataFixtures\RoRo\CountryFixtures;
use App\Tests\DataFixtures\RoRo\RouteAndPortFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * What a freshly migrated database already contains (the kernel tests build their schema from the ORM mapping,
 * so they can't run the MySQL-only migrations): ports and routes (RouteAndPortFixtures: 3 active routes, 1
 * inactive), countries, the three operator groups, and one operator with no users, surveys or routes, like the
 * migration-created "Stena Line - Irish". And an empty messenger queue: the fixture purger doesn't clear
 * messenger_messages, so a message left by an earlier test would otherwise fail the baseline guard.
 */
class BaselineReferenceFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // (Not tablesExist(): doctrine.yaml's schema_filter hides messenger_messages from the schema manager.)
        assert($manager instanceof EntityManagerInterface);
        try {
            $manager->getConnection()->executeStatement('DELETE FROM messenger_messages');
        } catch (TableNotFoundException) {
            // no queue yet: nothing to clear
        }

        foreach (['Brittany Ferries', 'DFDS Seaways', 'Stena Line'] as $name) {
            $manager->persist((new OperatorGroup())->setName($name));
        }

        $operator = (new Operator())->setName('Stena Line - Irish')->setCode(26)->setIsActive(true);
        $manager->persist($operator);
        $this->setReference('baseline:operator', $operator);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [CountryFixtures::class, RouteAndPortFixtures::class];
    }
}
