<?php

namespace App\Repository\International;

use App\Entity\International\SamplingGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SamplingGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method SamplingGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method SamplingGroup[]    findAll()
 * @method SamplingGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SamplingGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SamplingGroup::class);
    }

    // /**
    //  * @return SamplingGroup[] Returns an array of SamplingGroup objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SamplingGroup
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
