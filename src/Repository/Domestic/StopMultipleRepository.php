<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\DayStop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DayStop|null find($id, $lockMode = null, $lockVersion = null)
 * @method DayStop|null findOneBy(array $criteria, array $orderBy = null)
 * @method DayStop[]    findAll()
 * @method DayStop[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StopMultipleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DayStop::class);
    }

    // /**
    //  * @return DayStop[] Returns an array of DayStop objects
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
    public function findOneBySomeField($value): ?DayStop
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
