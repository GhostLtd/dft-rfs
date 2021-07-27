<?php

namespace App\Repository\International;

use App\Entity\International\SamplingSchema;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SamplingSchema|null find($id, $lockMode = null, $lockVersion = null)
 * @method SamplingSchema|null findOneBy(array $criteria, array $orderBy = null)
 * @method SamplingSchema[]    findAll()
 * @method SamplingSchema[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SamplingSchemaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SamplingSchema::class);
    }

    // /**
    //  * @return SamplingSchema[] Returns an array of SamplingSchema objects
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
    public function findOneBySomeField($value): ?SamplingSchema
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
