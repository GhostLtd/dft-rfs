<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DayStop|null find($id, $lockMode = null, $lockVersion = null)
 * @method DayStop|null findOneBy(array $criteria, array $orderBy = null)
 * @method DayStop[]    findAll()
 * @method DayStop[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DayStopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DayStop::class);
    }

    /**
     * @param Survey $survey
     * @param $dayNumber
     * @return DaySummary
     * @throws NonUniqueResultException
     */
    public function getBySurveyAndDayNumber(Survey $survey, $dayNumber)
    {
        $dayStop = $this->createQueryBuilder('day_stop')
            ->select('day_stop, day')
            ->leftJoin('day_stop.day', 'day')
            ->leftJoin('day.response', 'response')
            ->where('day.number = :dayNumber')
            ->andWhere('response.survey = :survey')
            ->setParameters([
                'dayNumber' => $dayNumber,
                'survey' => $survey,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (is_null($dayStop)) {
            $dayStop = new DayStop();
            $dayStop->setDay($survey->getResponse()->getDayByNumber($dayNumber));
        }
        return $dayStop;
    }

    public function findForExport($surveyIDs)
    {
        return $this->createQueryBuilder('day_stop')
            ->select('day_stop, day, summary')
            ->leftJoin('day_stop.day', 'day')
            ->leftJoin('day.response', 'response')
            ->leftJoin('day.summary', 'summary')
            ->leftJoin('response.survey', 'survey')
            ->where('survey.id in (:surveyIDs)')
            ->setParameter('surveyIDs', $surveyIDs)
            ->orderBy('survey.surveyPeriodStart', 'ASC')
            ->addOrderBy('survey.id', 'ASC')
            ->addOrderBy('day.number', 'ASC')
            ->addOrderBy('day_stop.number', 'ASC')
            ->getQuery()
            ->execute();
    }

    public function findOneForDelete(string $stopId): ?DayStop
    {
        try {
            return $this->createQueryBuilder('ds')
                ->select('ds, day, stops')
                ->leftJoin('ds.day', 'day')
                ->leftJoin('day.stops', 'stops')
                ->where('ds.id = :stopId')
                ->setParameters([
                    'stopId' => $stopId,
                ])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
