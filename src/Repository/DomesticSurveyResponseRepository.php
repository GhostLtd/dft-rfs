<?php

namespace App\Repository;

use App\Entity\DomesticSurveyResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DomesticSurveyResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomesticSurveyResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomesticSurveyResponse[]    findAll()
 * @method DomesticSurveyResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomesticSurveyResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomesticSurveyResponse::class);
    }

    // /**
    //  * @return DomesticSurveyResponse[] Returns an array of DomesticSurveyResponse objects
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
    public function findOneBySomeField($value): ?DomesticSurveyResponse
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
