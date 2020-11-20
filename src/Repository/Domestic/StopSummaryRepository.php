<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DaySummary|null find($id, $lockMode = null, $lockVersion = null)
 * @method DaySummary|null findOneBy(array $criteria, array $orderBy = null)
 * @method DaySummary[]    findAll()
 * @method DaySummary[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StopSummaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DaySummary::class);
    }

    /**
     * @param $dayNumber
     * @return DaySummary
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getForDevelopmentByDayNumber($dayNumber)
    {
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findLatestSurveyForTesting();
        $daySummary = $this->createQueryBuilder('stop_summary')
            ->select('stop_summary, day')
            ->leftJoin('stop_summary.day', 'day')
            ->leftJoin('day.response', 'response')
            ->where('day.number = :dayNumber')
            ->andWhere('response.survey = :survey')
            ->setParameters([
                'dayNumber' => dump($dayNumber),
                'survey' => $survey,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (is_null($daySummary)) {
            $daySummary = new DaySummary();
            $daySummary->setDay(dump($survey)->getResponse()->getDayByNumber($dayNumber));
        }
        return $daySummary;
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
