<?php

namespace App\Repository\AuditLog;

use App\Entity\AuditLog\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function getLogs(string $entityId, string $entityClass): array
    {
        return $this
            ->createQueryBuilder('l')
            ->where('l.entityClass = :entityClass')
            ->andWhere('l.entityId = :entityId')
            ->orderBy('l.timestamp', 'DESC')
            ->getQuery()
            ->setParameters([
                'entityId' => $entityId,
                'entityClass' => $entityClass,
            ])
            ->execute();
    }
}
