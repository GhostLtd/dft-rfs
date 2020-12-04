<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SurveyResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveyResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyResponse[]    findAll()
 * @method SurveyResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyResponse::class);
    }

    /**
     * @param Survey $survey
     * @param $dayNumber
     * @return DaySummary
     * @throws NonUniqueResultException
     */
    public function getBySurvey(Survey $survey)
    {
        return $this->createQueryBuilder('survey_response')
            ->select('survey_response, vehicle')
            ->leftJoin('survey_response.vehicle', 'vehicle')
            ->leftJoin('survey_response.days', 'days')
            ->andWhere('survey_response.survey = :survey')
            ->setParameters([
                'survey' => $survey,
            ])
            ->getQuery()
            ->getOneOrNullResult();
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
