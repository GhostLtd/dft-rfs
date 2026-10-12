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

    /**
     * getLogs() memoises (the admin survey view asks for the same logs more than once). The memo must be
     * per-instance - i.e. per request - and not process-wide, or a long-lived process (a messenger consumer, or
     * the in-process functional tests) keeps serving the logs as they were at the first call.
     */
    public function testGetLogsMemoDoesNotOutliveTheRepositoryInstance(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.AuditLog::class)->execute();

        $entityId = 'audit-log-repository-memo-test';
        $newLog = fn() => (new AuditLog())
            ->setEntityClass(Survey::class)->setEntityId($entityId)->setTimestamp(new \DateTime())
            ->setUsername('tester')->setCategory('test')->setData([]);

        $entityManager->persist($newLog());
        $entityManager->flush();

        $repository = $container->get(AuditLogRepository::class);
        self::assertCount(1, $repository->getLogs($entityId, Survey::class));

        $entityManager->persist($newLog());
        $entityManager->flush();

        self::assertCount(1, $repository->getLogs($entityId, Survey::class), 'the same instance memoises');

        $nextRequestsRepository = new AuditLogRepository($container->get('doctrine'));
        self::assertCount(2, $nextRequestsRepository->getLogs($entityId, Survey::class), 'a new instance must not see the old memo');
    }
}
