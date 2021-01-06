<?php

namespace App\Repository;

use App\Entity\BlameLog\BlameLog;
use App\Entity\BlameLoggable;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class BlameLogRepository extends EntityRepository
{
    public const INITIAL_ENTRY_COUNT = 5;

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
