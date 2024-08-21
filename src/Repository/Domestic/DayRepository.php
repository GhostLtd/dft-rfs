<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Day|null find($id, $lockMode = null, $lockVersion = null)
 * @method Day|null findOneBy(array $criteria, array $orderBy = null)
 * @method Day[]    findAll()
 * @method Day[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Day::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getBySurveyAndDayNumber(Survey $survey, int $dayNumber): ?Day
    {
        return $this->createQueryBuilder('day')
            ->select('day, stops')
            ->leftJoin('day.stops', 'stops')
            ->leftJoin('day.response', 'response')
            ->where('day.number = :dayNumber')
            ->andWhere('response.survey = :survey')
            ->setParameters(new ArrayCollection([
                new Parameter('dayNumber', $dayNumber),
                new Parameter('survey', $survey),
            ]))
            ->getQuery()
            ->getOneOrNullResult();
    }


    // /**
    //  * @return DomesticStopDay[] Returns an array of DomesticStopDay objects
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
    public function findOneBySomeField($value): ?DomesticStopDay
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
