<?php

namespace App\Tests\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

class DoctrineMigrationFixtures extends Fixture implements FixtureInterface
{
    /**
     * @param EntityManagerInterface $manager
     */
    public function load(ObjectManager $manager)
    {
        $finder = new Finder();
        $finder->files()->in('migrations');

        $connection = $manager->getConnection();
        $connection->beginTransaction();
        $connection->executeQuery('create table doctrine_migration_versions (version varchar(191) not null primary key, executed_at datetime null, execution_time int null)');

        foreach($finder as $file) {
            $connection->createQueryBuilder()
                ->insert('doctrine_migration_versions')
                ->values([
                    'version' => ':version',
                    'executed_at' => ':timestamp',
                    'execution_time' => ':execTime',
                ])
                ->setParameters([
                    'version' => 'DoctrineMigrations\\' . $file->getFilenameWithoutExtension(),
                    'timestamp' => (new \DateTime())->getTimestamp(),
                    'execTime' => 1,
                ])
                ->execute();
        }

        $connection->commit();
    }
}