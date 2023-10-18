<?php

namespace App\Repository\International;

use App\Entity\International\Survey;
use App\Entity\SurveyStateInterface;
use App\Repository\AbstractSurveyRepository;
use App\Repository\DashboardStatsTrait;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends AbstractSurveyRepository
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
            ->setParameters([
                'id' => $id,
            ])
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

    /**
     * @return Survey[]|array
     */
    public function getSurveysRequiringPasscodeUserCleanup(\DateTime $before): array
    {
        return $this->doGetSurveysRequiringPasscodeUserCleanup($before, 'international_survey');
    }

    /**
     * @return Survey[]|array
     */
    public function getSurveysRequiringPersonalDataCleanup(\DateTime $before): array {
        $states = [SurveyStateInterface::STATE_REJECTED, SurveyStateInterface::STATE_EXPORTED];
        return $this->doGetSurveysRequiringPersonalDataCleanup($before, $states, 'international_survey');
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
            ->andWhere('s.surveyPeriodEnd >= :minDate')
            ->andWhere('s.surveyPeriodEnd < :maxDate')
            ->getQuery()
            ->setParameters([
                'minDate' => $minDate,
                'maxDate' => $maxDate,
            ])
            ->execute();
    }
}
