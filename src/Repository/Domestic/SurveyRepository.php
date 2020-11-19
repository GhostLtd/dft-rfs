<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
    }

    /**
     * @return Survey
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLatestSurveyForTesting()
    {
        return $this->createQueryBuilder('survey')
            ->select('survey, response, vehicle, day, summary')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.days', 'day', null, null, 'day.number')
            ->leftJoin('response.vehicle', 'vehicle')
            ->leftJoin('day.summary', 'summary')
            ->orderBy('survey.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    // /**
    //  * @return Survey[] Returns an array of Survey objects
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
    public function findOneBySomeField($value): ?Survey
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
