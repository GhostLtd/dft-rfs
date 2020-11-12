<?php

namespace App\Repository;

use App\Entity\InternationalTrip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InternationalTrip|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternationalTrip|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternationalTrip[]    findAll()
 * @method InternationalTrip[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternationalTripRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternationalTrip::class);
    }

    // /**
    //  * @return InternationalTrip[] Returns an array of InternationalTrip objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InternationalTrip
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
