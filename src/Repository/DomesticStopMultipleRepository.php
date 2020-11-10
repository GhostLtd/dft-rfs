<?php

namespace App\Repository;

use App\Entity\DomesticStopMultiple;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DomesticStopMultiple|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomesticStopMultiple|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomesticStopMultiple[]    findAll()
 * @method DomesticStopMultiple[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomesticStopMultipleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomesticStopMultiple::class);
    }

    // /**
    //  * @return DomesticStopMultiple[] Returns an array of DomesticStopMultiple objects
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
    public function findOneBySomeField($value): ?DomesticStopMultiple
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
