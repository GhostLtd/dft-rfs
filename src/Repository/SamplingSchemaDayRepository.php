<?php

namespace App\Repository;

use App\Entity\SamplingSchemaDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SamplingSchemaDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method SamplingSchemaDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method SamplingSchemaDay[]    findAll()
 * @method SamplingSchemaDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SamplingSchemaDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SamplingSchemaDay::class);
    }

    // /**
    //  * @return SamplingSchemaDay[] Returns an array of SamplingSchemaDay objects
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
    public function findOneBySomeField($value): ?SamplingSchemaDay
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
