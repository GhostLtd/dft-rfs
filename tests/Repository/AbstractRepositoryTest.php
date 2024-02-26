<?php

namespace App\Tests\Repository;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractRepositoryTest extends KernelTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected EntityManagerInterface $entityManager;
    protected ReferenceRepository $fixtureReferenceRepository;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();

        $container = static::getContainer()->get('test.service_container');
        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();

        $entityManager = $container->get(EntityManagerInterface::class);

        assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
    }

    protected function loadFixtures(array $classNames = [], bool $append = false): ?AbstractExecutor
    {
        $fixtures = $this->databaseTool->loadFixtures($classNames, $append);
        $this->fixtureReferenceRepository = $fixtures->getReferenceRepository();

        return $fixtures;
    }

    /**
     * @param string $entityName The name of the entity.
     * @psalm-param class-string<T> $entityName
     *
     * @return ObjectRepository|EntityRepository The repository class.
     * @psalm-return EntityRepository<T>
     *
     * @template T of object
     */
    protected function getRepository(string $entityName): ObjectRepository|EntityRepository
    {
        return $this->entityManager->getRepository($entityName);
    }
}