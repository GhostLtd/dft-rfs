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
    // /**
    //  * @return DayStop[] Returns an array of DayStop objects
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
    public function findOneBySomeField($value): ?DayStop
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
