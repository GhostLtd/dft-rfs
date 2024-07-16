<?php

namespace App\Repository\International;

use App\Entity\International\Action;
use App\Entity\International\SurveyResponse;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
                ->setParameter('actionId', $id)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    /**
     * @return array<Action>
     */
    public function getLoadingActions(string $tripId): array
    {
        return $this->createQueryBuilder('a')
            ->select('a, ua')
            ->where('a.trip = :tripId')
            ->andWhere('a.loading = :loading')
            ->leftJoin('a.unloadingActions', 'ua')
            ->setParameters(new ArrayCollection([
                new Parameter('tripId', $tripId),
                new Parameter('loading', true),
            ]))
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
                ->setParameters(new ArrayCollection([
                    new Parameter('actionId', $id),
                    new Parameter('response', $response),
                ]))
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    /**
     * @return array<Action>
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
            ->setParameters(new ArrayCollection([
                new Parameter('isLoading', false),
                new Parameter('weekStart', $weekStart),
                new Parameter('weekEnd', $weekEnd),
            ]))
            ->execute();
    }
}
