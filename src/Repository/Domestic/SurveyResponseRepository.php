<?php

namespace App\Repository\Domestic;

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
     * @throws NonUniqueResultException
     */
    public function getBySurvey(Survey $survey): ?SurveyResponse
    {
        return $this->createQueryBuilder('survey_response')
            ->select('survey_response, vehicle')
            ->leftJoin('survey_response.survey', 'survey')
            ->leftJoin('survey_response.vehicle', 'vehicle')
            ->leftJoin('survey_response.days', 'days')
            ->andWhere('survey_response.survey = :survey')
            ->setParameter('survey', $survey)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
