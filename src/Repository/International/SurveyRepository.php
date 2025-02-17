<?php

namespace App\Repository\International;

use App\Entity\International\Survey;
use App\Entity\SurveyStateInterface;
use App\Repository\DashboardStatsTrait;
use App\Repository\SurveyDeletionInterface;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository implements SurveyDeletionInterface
{
    use DashboardStatsTrait;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Survey::class);
    }

    public function findWithVehiclesAndTrips(string $id): ?Survey
    {
        return $this->createQueryBuilder('s')
            ->select('s, r, v, t, a, p')
            ->leftJoin('s.response', 'r')
            ->leftJoin('r.vehicles', 'v')
            ->leftJoin('v.trips', 't')
            ->leftJoin('t.actions', 'a')
            ->leftJoin('s.passcodeUser', 'p')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getOverdueCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.surveyPeriodEnd < :now')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameter('now', new \DateTime())
            ->setParameter('activeStates', SurveyStateInterface::ACTIVE_STATES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInProgressCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.state IN (:activeStates)')
            ->setParameter('activeStates', SurveyStateInterface::ACTIVE_STATES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getSurveysForExport(DateTime $minDate, DateTime $maxDate): array
    {
        return $this->createQueryBuilder('s')
            ->select('s, r, v, t, p')
            ->leftJoin('s.response', 'r')
            ->leftJoin('r.vehicles', 'v')
            ->leftJoin('v.trips', 't')
            // If we don't join on user, it does n+1 queries
            ->leftJoin('s.passcodeUser', 'p')
//            ->where(new Expr\Orx([
//                // start is within dates
//                new Expr\Andx(['s.surveyPeriodStart >= :minDate', 's.surveyPeriodStart < :maxDate']),
//                // end is within dates
//                new Expr\Andx(['s.surveyPeriodEnd >= :minDate', 's.surveyPeriodEnd < :maxDate']),
//                // start is before first date, and end is after last date (encompassing)
//                new Expr\Andx(['s.surveyPeriodStart < :minDate', 's.surveyPeriodEnd >= :maxDate']),
//            ]))
            ->where(new Expr\Andx([
                's.surveyPeriodEnd >= :minDate',
                's.surveyPeriodStart < :maxDate',
            ]))
            ->getQuery()
            ->setParameters(new ArrayCollection([
                new Parameter('minDate', $minDate),
                new Parameter('maxDate', $maxDate),
            ]))
            ->execute();
    }

    public function getSurveysForDeletion(\DateTime $before): array
    {
        return $this->createQueryBuilder('survey')
            ->select('survey, response, notes, vehicles, trips')
            ->where('survey.surveyPeriodEnd < :before')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('survey.notes', 'notes')
            ->leftJoin('response.vehicles', 'vehicles')
            ->leftJoin('vehicles.trips', 'trips')
            ->setParameter('before', $before->format('Y-m-d'))
            ->getQuery()
            ->execute();
    }
}
