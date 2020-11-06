<?php

namespace App\Repository;

use App\Entity\DomesticSurvey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DomesticSurvey|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomesticSurvey|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomesticSurvey[]    findAll()
 * @method DomesticSurvey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomesticSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomesticSurvey::class);
    }

    // /**
    //  * @return DomesticSurvey[] Returns an array of DomesticSurvey objects
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
    public function findOneBySomeField($value): ?DomesticSurvey
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