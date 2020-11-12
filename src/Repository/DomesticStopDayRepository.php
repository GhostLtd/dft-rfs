<?php

namespace App\Repository;

use App\Entity\DomesticStopDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DomesticStopDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomesticStopDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomesticStopDay[]    findAll()
 * @method DomesticStopDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomesticStopDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomesticStopDay::class);
    }

    // /**
    //  * @return DomesticStopDay[] Returns an array of DomesticStopDay objects
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
    public function findOneBySomeField($value): ?DomesticStopDay
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
