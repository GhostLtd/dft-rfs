<?php

namespace App\Repository;

use App\Entity\BlameLog\BlameLog;
use App\Entity\BlameLoggable;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class BlameLogRepository extends EntityRepository
{
    public const INITIAL_ENTRY_COUNT = 5;

    public function getApprovingUsername(BlameLoggable $entity)
    {
        return $this->createQueryBuilder('blame_log')
            ->select('blame_log.userId as user, blame_log.date')
            ->where('blame_log.entityId = :id')
            ->andWhere('blame_log.class = :class')
            ->andWhere('blame_log.properties LIKE :props')
            ->setParameters([
                'id' => $entity->getId(),
                'class' => get_class($entity),
                'props' => '%"state":["closed","approved"]%',
            ])
            ->orderBy('blame_log.date', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function getAllLogsForEntity(BlameLoggable $entity, $maxDepth = 3)
    {
        $relatedIds = $ids = [
            $entity->getId(),
        ];

        for ($depth = 1; $depth <= $maxDepth; $depth++)
        {
            $relatedIds = $this->getRelatedIdsFromLog($relatedIds);
            $ids = array_merge($ids, $relatedIds);
        }

        return $this->createQueryBuilder('blame_log')
            ->select('blame_log')
            ->where('blame_log.entityId IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('blame_log.date', 'asc')
            ->getQuery()
            ->getResult()
            ;
    }

    protected function getRelatedIdsFromLog(array $entityIds)
    {
        $result = $this->createQueryBuilder('blame_log')
            ->select('blame_log.entityId')
            ->distinct()
            ->andWhere('blame_log.associatedId IN (:assocId)')
            ->setParameters([
                'assocId' => $entityIds,
            ])
            ->getQuery()
            ->getArrayResult();
        return array_column($result, 'entityId');
    }

    /**
     * @param $class
     * @return BlameLog[]
     */
    public function getInitialLogsForClass($class)
    {
        return $this->getQueryBuilderForClass($class)
            ->setFirstResult(0)
            ->setMaxResults(self::INITIAL_ENTRY_COUNT)
            ->getQuery()
            ->execute();
    }

    public function getInitialLogsForEntity(BlameLoggable $entity)
    {
        return $this->getQueryBuilderForEntity($entity)
            ->setFirstResult(0)
            ->setMaxResults(self::INITIAL_ENTRY_COUNT)
            ->getQuery()
            ->execute();
    }

    public function getQueryBuilderForEntity(BlameLoggable $entity) {
        return $this->createQueryBuilder('b')
            ->where('b.entityId = :entityId')
            ->andWhere('b.class = :class')
            ->setParameters([
                'class' => get_class($entity),
                'entityId' => $entity->getId(),
            ])
            ->orderBy('b.date', 'DESC')
            ->addOrderBy('b.id', 'DESC');
    }

    /**
     * @param $class
     * @return QueryBuilder
     */
    public function getQueryBuilderForClass($class) {
        return $this->createQueryBuilder('b')
            ->andWhere('b.class = :class')
            ->setParameter('class', $class)
            ->orderBy('b.date', 'DESC')
            ->addOrderBy('b.id', 'DESC');
    }
}
