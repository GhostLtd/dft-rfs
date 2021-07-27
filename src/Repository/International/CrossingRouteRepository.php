<?php

namespace App\Repository\International;

use App\Entity\International\CrossingRoute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CrossingRoute|null find($id, $lockMode = null, $lockVersion = null)
 * @method CrossingRoute|null findOneBy(array $criteria, array $orderBy = null)
 * @method CrossingRoute[]    findAll()
 * @method CrossingRoute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CrossingRouteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrossingRoute::class);
    }

    // /**
    //  * @return CrossingRoute[] Returns an array of CrossingRoute objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CrossingRoute
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
