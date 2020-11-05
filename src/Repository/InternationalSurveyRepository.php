<?php

namespace App\Repository;

use App\Entity\InternationalSurvey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InternationalSurvey|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternationalSurvey|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternationalSurvey[]    findAll()
 * @method InternationalSurvey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternationalSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternationalSurvey::class);
    }

    // /**
    //  * @return InternationalSurvey[] Returns an array of InternationalSurvey objects
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
    public function findOneBySomeField($value): ?InternationalSurvey
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
