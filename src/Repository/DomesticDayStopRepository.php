<?php

namespace App\Repository;

use App\Entity\DomesticDayStop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DomesticDayStop|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomesticDayStop|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomesticDayStop[]    findAll()
 * @method DomesticDayStop[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomesticDayStopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomesticDayStop::class);
    }

    // /**
    //  * @return DomesticDayStop[] Returns an array of DomesticDayStop objects
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
    public function findOneBySomeField($value): ?DomesticDayStop
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
