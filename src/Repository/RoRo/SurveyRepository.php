<?php

namespace App\Repository\RoRo;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\Route\Route;
use App\Entity\SurveyStateInterface;
use App\Repository\SurveyDeletionInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Survey>
 *
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository implements SurveyDeletionInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
    }

    /**
     * @return array<OperatorRoute>
     */
    public function getOperatorRoutesWithNoSurveysForSurveyPeriodStart(\DateTime $surveyPeriodStart): array
    {
        $existsPart = $this->createQueryBuilder('s2')
            ->where('s2.surveyPeriodStart = :surveyPeriodStart')
            ->andWhere('s2.operator = operator')
            ->andWhere('s2.route = route')
            ->getDQL();

        $queryBuilder = $this->createQueryBuilder('survey');

        // Find all operator routes, where:
        // a) The operator is active
        // b) The route is active
        // c) No survey exists for that operator and route on the given surveyPeriodStart date
        return $this->getEntityManager()
            ->getRepository(Operator::class)
            ->createQueryBuilder('operator')
            ->select(sprintf('NEW %s(operator.id, route.id)', OperatorRoute::class))
            ->join('operator.routes', 'route')
            ->where('operator.isActive = 1')
            ->andWhere('route.isActive = 1')
            ->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($existsPart)))
            ->getQuery()
            ->setParameter('surveyPeriodStart', $surveyPeriodStart)
            ->getResult();
    }

    /**
     * @return array<Survey>
     */
    public function getSurveysForOperatorRouteInDateRange(Operator $operator, Route $route, \DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('survey')
            ->select('survey')
            ->join('survey.operator', 'operator')
            ->join('survey.route', 'route')
            ->where('operator.isActive = 1')
            ->andWhere('route.isActive = 1')
            ->andWhere('operator.id = :operatorId')
            ->andWhere('route.id = :routeId')
            ->andWhere('survey.surveyPeriodStart >= :start')
            ->andWhere('survey.surveyPeriodStart <= :end')
            ->getQuery()
            ->setParameters(new ArrayCollection([
                new Parameter('operatorId', $operator->getId()),
                new Parameter('routeId', $route->getId()),
                new Parameter('start', $start),
                new Parameter('end', $end),
            ]))
            ->getResult();
    }

    /**
     * @return array<Survey>
     */
    public function getAllSurveysForOperator(Operator $operator): array
    {
        return $this->createQueryBuilder('survey')
            ->select('survey')
            ->join('survey.operator', 'operator')
            ->where('operator.id = :operatorId')
            ->getQuery()
            ->setParameter('operatorId', $operator->getId())
            ->getResult();
    }

    /**
     * @return array<Survey>
     */
    public function getSurveysForOperator(Operator $operator): array
    {
        $queryBuilder = $this->createQueryBuilder('survey');

        return $queryBuilder
            ->select('survey, route, uk_port, foreign_port, operator')
            ->join('survey.route', 'route')
            ->join('route.ukPort', 'uk_port')
            ->join('route.foreignPort', 'foreign_port')
            ->join('survey.operator', 'operator')
            ->andWhere('operator.isActive = 1')
            ->andWhere('operator.id = :operatorId')
            ->orderBy('survey.surveyPeriodStart')
            ->addOrderBy('uk_port.name')
            ->addOrderBy('foreign_port.name')
            ->setParameter('operatorId', $operator->getId())
            ->getQuery()
            ->getResult();
    }

    public function getSurveyPeriodStartForYearAndMonth(int $currentYear, int $currentMonth): \DateTime
    {
        $monthPadded = str_pad($currentMonth, 2, '0', STR_PAD_LEFT);
        return \DateTime::createFromFormat(\DateTime::ATOM, "{$currentYear}-{$monthPadded}-01T00:00:00+00:00");
    }

    public function findForExportMonth(int $year, int $month): array
    {
        $monthStart = \DateTimeImmutable::createFromFormat('Y-m-d', "$year-$month-1")->modify('midnight first day of this month');
        $monthEnd = $monthStart->modify('+1 month');

        return $this->createQueryBuilder('survey')
            ->select('survey')
            ->join('survey.operator', 'operator')
            ->join('survey.route', 'route')
            ->join('route.ukPort', 'uk_port')
            ->join('route.foreignPort', 'foreign_port')
            ->join('survey.vehicleCounts', 'vehicle_count')
            ->where('survey.state IN (:states)')
            ->andWhere('survey.surveyPeriodStart >= :monthStart')
            ->andWhere('survey.surveyPeriodStart < :monthEnd')
            ->setParameters(new ArrayCollection([
                new Parameter('states', [SurveyStateInterface::STATE_APPROVED, SurveyStateInterface::STATE_CLOSED]),
                new Parameter('monthStart', $monthStart),
                new Parameter('monthEnd', $monthEnd),
            ]))
            ->getQuery()
            ->execute();
    }

    public function getOverdueCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.surveyPeriodStart < :sevenDaysAgo')
            ->andWhere('s.state IN (:states)')
            ->setParameters(new ArrayCollection([
                new Parameter('states', SurveyStateInterface::ACTIVE_STATES),
                new Parameter('sevenDaysAgo', (new \DateTime())->modify('-7 days')),
            ]))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInProgressCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.state IN (:states)')
            ->setParameter('states', SurveyStateInterface::ACTIVE_STATES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getSurveysForDeletion(\DateTime $before): array
    {
        return $this->createQueryBuilder('survey')
            ->select('survey, vc')
            ->leftJoin('survey.vehicleCounts', 'vc')
            ->leftJoin('survey.notes', 'notes')
            ->where('survey.surveyPeriodStart < :before')
            ->setParameter('before', $before->format('Y-m-d'))
            ->getQuery()
            ->execute();
    }
}
