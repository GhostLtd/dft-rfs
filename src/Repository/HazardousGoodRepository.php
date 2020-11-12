<?php

namespace App\Repository;

use App\Entity\HazardousGood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HazardousGood|null find($id, $lockMode = null, $lockVersion = null)
 * @method HazardousGood|null findOneBy(array $criteria, array $orderBy = null)
 * @method HazardousGood[]    findAll()
 * @method HazardousGood[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HazardousGoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HazardousGood::class);
    }

    // /**
    //  * @return HazardousGood[] Returns an array of HazardousGood objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HazardousGood
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
