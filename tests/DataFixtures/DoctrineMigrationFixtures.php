<?php

namespace App\Tests\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

class DoctrineMigrationFixtures extends Fixture implements FixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $finder = new Finder();
        $finder->files()->in('migrations');

        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Expected ObjectManager to be instance of EntityManagerInterface');
        }

        $connection = $manager->getConnection();
        $connection->beginTransaction();
        $connection->executeQuery('CREATE TABLE IF NOT EXISTS doctrine_migration_versions (version varchar(191) not null primary key, executed_at datetime null, execution_time int null)');
        $connection->executeQuery('DELETE FROM doctrine_migration_versions WHERE TRUE=TRUE');

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
                    'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'execTime' => 1,
                ])
                ->executeQuery();
        }

        $connection->commit();
    }
}
