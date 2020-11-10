<?php

namespace App\Repository;

use App\Entity\DomesticStopSummary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DomesticStopSummary|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomesticStopSummary|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomesticStopSummary[]    findAll()
 * @method DomesticStopSummary[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomesticStopSummaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomesticStopSummary::class);
    }

    // /**
    //  * @return DomesticStopSummary[] Returns an array of DomesticStopSummary objects
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
    public function findOneBySomeField($value): ?DomesticStopSummary
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
