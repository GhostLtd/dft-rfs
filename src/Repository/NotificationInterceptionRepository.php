<?php

namespace App\Repository;

use App\Entity\NotificationInterception;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NotificationInterception|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationInterception|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotificationInterception[]    findAll()
 * @method NotificationInterception[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationInterceptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationInterception::class);
    }

    public function findOneByBusinessName(string $businessName): ?NotificationInterception
    {
        return $this->createQueryBuilder('ni')
            ->leftJoin('ni.names', 'nn')
            ->andWhere('nn.name = :name')
            ->setParameter('name', $this->normaliseBusinessName($businessName))
            ->getQuery()
            ->getOneOrNullResult();
    }

    protected function normaliseBusinessName(string $businessName): string
    {
        // Collapse all whitespace (including NBSP) to single space, then trim
        return strtolower(mb_trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $businessName)));
    }
}
