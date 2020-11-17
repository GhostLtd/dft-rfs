<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\DaySummary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DaySummary|null find($id, $lockMode = null, $lockVersion = null)
 * @method DaySummary|null findOneBy(array $criteria, array $orderBy = null)
 * @method DaySummary[]    findAll()
 * @method DaySummary[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StopSummaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DaySummary::class);
    }

    // /**
    //  * @return DaySummary[] Returns an array of DaySummary objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DaySummary
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
