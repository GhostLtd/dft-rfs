<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DaySummary|null find($id, $lockMode = null, $lockVersion = null)
 * @method DaySummary|null findOneBy(array $criteria, array $orderBy = null)
 * @method DaySummary[]    findAll()
 * @method DaySummary[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DaySummaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DaySummary::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getBySurveyAndDay(Survey $survey, Day $day): ?DaySummary
    {
        return $this->createQueryBuilder('day_summary')
            ->select('day_summary, day')
            ->leftJoin('day_summary.day', 'day')
            ->leftJoin('day.response', 'response')
            ->where('day = :day')
            ->andWhere('response.survey = :survey')
            ->setParameters(new ArrayCollection([
                new Parameter('day', $day),
                new Parameter('survey', $survey),
            ]))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForExport($surveyIDs) {
        return $this->createQueryBuilder('day_summary')
            ->select('day_summary, day, response')
            ->leftJoin('day_summary.day', 'day')
            ->leftJoin('day.response', 'response')
            ->leftJoin('response.survey', 'survey')
            ->where('survey.id in (:surveyIDs)')
            ->setParameter('surveyIDs', $surveyIDs)
            ->orderBy('survey.surveyPeriodStart', 'ASC')
            ->addOrderBy('survey.id', 'ASC')
            ->addOrderBy('day.number', 'ASC')
            ->getQuery()
            ->execute();
    }

    // /**
    //  * @return DaySummary[] Returns an array of DaySummary objects
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
    public function findOneBySomeField($value): ?DaySummary
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
