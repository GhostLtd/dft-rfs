<?php

namespace App\Repository\International;

use App\Entity\International\Action;
use App\Entity\International\SurveyResponse;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Action|null find($id, $lockMode = null, $lockVersion = null)
 * @method Action|null findOneBy(array $criteria, array $orderBy = null)
 * @method Action[]    findAll()
 * @method Action[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Action::class);
    }

    public function getNextNumber(string $tripId): int
    {
        $currentMax = $this->createQueryBuilder('a')
            ->select('max(a.number)')
            ->where('a.trip = :tripId')
            ->setParameter('tripId', $tripId)
            ->getQuery()
            ->getSingleScalarResult();

        return $currentMax ? ($currentMax + 1) : 1;
    }

    public function findOneByIdWithRelatedActions(string $id)
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('a,la,t,v,r,ua,s')
                ->leftJoin('a.trip', 't')
                ->leftJoin('t.vehicle', 'v')
                ->leftJoin('v.surveyResponse', 'r')
                ->leftJoin('r.survey', 's')
                ->leftJoin('a.loadingAction', 'la')
                ->leftJoin('a.unloadingActions', 'ua')
                ->where('a.id = :actionId')
                ->setParameters([
                    'actionId' => $id,
                ])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @return ArrayCollection|Action[]
     */
    public function getLoadingActions(string $tripId): array
    {
        return $this->createQueryBuilder('a')
            ->select('a, ua')
            ->where('a.trip = :tripId')
            ->andWhere('a.loading = :loading')
            ->leftJoin('a.unloadingActions', 'ua')
            ->setParameters([
                'tripId' => $tripId,
                'loading' => true,
            ])
            ->orderBy('a.number', 'ASC')
            ->getQuery()
            ->execute();
    }

    public function findOneByIdAndSurveyResponse(string $id, SurveyResponse $response): ?Action
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('a,la,t,v,r,ua')
                ->leftJoin('a.trip', 't')
                ->leftJoin('t.vehicle', 'v')
                ->leftJoin('v.surveyResponse', 'r')
                ->leftJoin('a.loadingAction', 'la')
                ->leftJoin('a.unloadingActions', 'ua')
                ->where('a.id = :actionId')
                ->andWhere('r = :response')
                ->setParameters([
                    'actionId' => $id,
                    'response' => $response,
                ])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @return Action[]|Collection
     */
    public function getActionsForExport(DateTime $weekStart, DateTime $weekEnd): array
    {
        return $this->createQueryBuilder('a')
            ->select('a,t,v,r,s')
            ->leftJoin('a.loadingAction', 'la')
            ->leftJoin('a.trip', 't')
            ->leftJoin('t.vehicle', 'v')
            ->leftJoin('v.surveyResponse', 'r')
            ->leftJoin('r.survey', 's')
            ->where('a.loading = :isLoading')
            ->andWhere('s.surveyPeriodStart >= :weekStart')
            ->andWhere('s.surveyPeriodStart < :weekEnd')
            ->getQuery()
            ->setParameters([
                'isLoading' => false,
                'weekStart' => $weekStart,
                'weekEnd' => $weekEnd,
            ])
            ->execute();
    }
}
