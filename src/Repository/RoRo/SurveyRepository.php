<?php

namespace App\Repository\RoRo;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\RoRoUser;
use App\Entity\Route\Route;
use App\Entity\SurveyStateInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Survey>
 *
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository
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
            ->setParameters([
                'operatorId' => $operator->getId(),
                'routeId' => $route->getId(),
                'start' => $start,
                'end' => $end,
            ])
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
            ->setParameters([
                'operatorId' => $operator->getId(),
            ])
            ->getResult();
    }

    /**
     * @return array<Survey>
     */
    public function getSurveysForUser(RoRoUser $user): array
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
            ->setParameters([
                'operatorId' => $user->getOperator()->getId(),
            ])
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
            ->where('survey.state = :state')
            ->andWhere('survey.surveyPeriodStart >= :monthStart')
            ->andWhere('survey.surveyPeriodStart < :monthEnd')
            ->setParameters([
                'state' => SurveyStateInterface::STATE_APPROVED,
                'monthStart' => $monthStart,
                'monthEnd' => $monthEnd,
            ])
            ->getQuery()
            ->execute();
    }
}
