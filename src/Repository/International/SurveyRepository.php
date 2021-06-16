<?php

namespace App\Repository\International;

use App\Entity\International\Company;
use App\Entity\International\Survey;
use App\Entity\SurveyInterface;
use App\Repository\DashboardStatsTrait;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository
{
    protected CompanyRepository $companyRepo;
    protected EntityManagerInterface $entityManager;

    use DashboardStatsTrait;

    public function __construct(ManagerRegistry $registry)
    {
        $this->entityManager = $registry->getManager();
        $this->companyRepo = $this->entityManager->getRepository(Company::class);

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

    /**
     * @return Survey[]|Collection
     */
    public function getSurveysForExport(DateTime $weekStart, DateTime $weekEnd): array
    {
        return $this->createQueryBuilder('s')
            ->select('s,r,v,t,a')
            ->leftJoin('s.response', 'r')
            ->leftJoin('r.vehicles', 'v')
            ->leftJoin('v.trips', 't')
            ->leftJoin('t.actions', 'a')
            ->leftJoin('a.loadingAction', 'la')
            ->where('s.state = :state')
            ->andWhere('s.surveyPeriodStart >= :weekStart')
            ->andWhere('s.surveyPeriodStart < :weekEnd')
            ->orderBy('s.surveyPeriodStart', 'ASC')
            ->addOrderBy('v.registrationMark', 'ASC')
            ->addOrderBy('a.number', 'ASC')
            ->getQuery()
            ->setParameters([
                'state' => Survey::STATE_APPROVED,
                'weekStart' => $weekStart,
                'weekEnd' => $weekEnd,
            ])
            ->execute();
    }

    public function getOverdueCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.surveyPeriodEnd < :now')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameter('now', new \DateTime())
            ->setParameter('activeStates', SurveyInterface::ACTIVE_STATES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInProgressCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.state IN (:activeStates)')
            ->setParameter('activeStates', SurveyInterface::ACTIVE_STATES)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
