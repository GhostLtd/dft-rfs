<?php

namespace App\Tests\Repository\AuditLog;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey;
use App\Repository\AuditLog\AuditLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AuditLogRepositoryTest extends KernelTestCase
{
    public function testGetLogsFindsRowsWhetherAskedWithTheMappedClassOrAProxyName(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.AuditLog::class)->execute();

        $entityId = 'audit-log-repository-test';
        $entityManager->persist((new AuditLog())
            ->setEntityClass(Survey::class)->setEntityId($entityId)->setTimestamp(new \DateTime())
            ->setUsername('tester')->setCategory('test')->setData([]));
        $entityManager->flush();

        $repository = $container->get(AuditLogRepository::class);
        self::assertCount(1, $repository->getLogs($entityId, Survey::class));
        self::assertCount(1, $repository->getLogs($entityId, 'Proxies\\__CG__\\'.Survey::class), 'a proxy class name resolves to the same rows');
    }
}
