<?php

namespace App\Repository\AuditLog;

use App\Entity\AuditLog\AuditLog;
use App\Entity\SurveyInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function surveyHasPreviouslyBeenInClosedState(SurveyInterface $entity): bool
    {
        $transitions = $this->createQueryBuilder('audit_log')
            ->select('audit_log')
            ->where('audit_log.entityId = :id')
            ->andWhere('audit_log.entityClass = :class')
            ->andWhere('audit_log.data LIKE :props')
            ->setParameters([
                'id' => $entity->getId(),
                'class' => get_class($entity),
                'props' => '%"' . SurveyInterface::STATE_CLOSED . '"%',
            ])
            ->orderBy('audit_log.timestamp', 'desc')
            ->getQuery()
            ->execute();
        return count($transitions) > 0;
    }

    public function getApprovedBy($entity)
    {
        return $this->createQueryBuilder('audit_log')
            ->select('audit_log.username, audit_log.timestamp')
            ->where('audit_log.entityId = :id')
            ->andWhere('audit_log.entityClass = :class')
            ->andWhere('audit_log.data LIKE :props')
            ->setParameters([
                'id' => $entity->getId(),
                'class' => get_class($entity),
                'props' => '%"to":"' . SurveyInterface::STATE_APPROVED . '"%',
            ])
            ->orderBy('audit_log.timestamp', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
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

    public function getQualityAssuredBy($entity)
    {
        return $this->createQueryBuilder('audit_log')
            ->select('audit_log.username, audit_log.timestamp')
            ->where('audit_log.entityId = :id')
            ->andWhere('audit_log.entityClass = :class')
            ->andWhere('audit_log.category = :category')
            ->setParameters([
                'id' => $entity->getId(),
                'class' => get_class($entity),
                'category' => 'survey-qa',
            ])
            ->orderBy('audit_log.timestamp', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
