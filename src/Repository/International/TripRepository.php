<?php

namespace App\Repository\International;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\SurveyStateInterface;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Trip|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trip|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trip[]    findAll()
 * @method Trip[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trip::class);
    }

    public function findOneByIdAndSurveyResponse(string $id, SurveyResponse $response): ?Trip
    {
        try {
            return $this->createQueryBuilder('t')
                ->select('t,v,r')
                ->leftJoin('t.vehicle', 'v')
                ->leftJoin('v.surveyResponse', 'r')
                ->where('t.id = :id')
                ->andWhere('r = :response')
                ->getQuery()
                ->setParameters(new ArrayCollection([
                    new Parameter('id', $id),
                    new Parameter('response', $response),
                ]))
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            // Not that this can ever happen, since we're querying by id!
            return null;
        }
    }

    public function findByIdAndSurveyResponse(string $id, SurveyResponse $response): ?Trip
    {
        try {
            return $this->createQueryBuilder('t')
                ->select('t,v,r')
                ->leftJoin('t.vehicle', 'v')
                ->leftJoin('v.surveyResponse', 'r')
                ->where('t.id = :id')
                ->andWhere('r = :response')
                ->getQuery()
                ->setParameters(new ArrayCollection([
                    new Parameter('id', $id),
                    new Parameter('response', $response),
                ]))
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    /**
     * @return array<Trip>
     */
    public function getTripsForExport(DateTime $minDate, DateTime $maxDate): array
    {
        return $this->createQueryBuilder('t')
            ->select('t,v,r,s,a,p')
            ->leftJoin('t.vehicle', 'v')
            ->leftJoin('v.surveyResponse', 'r')
            ->leftJoin('r.survey', 's')
            ->leftJoin('s.passcodeUser', 'p')
            ->leftJoin('t.actions', 'a')
                // Do not link to loadingAction, as this was limiting the query (actions that had not been unloaded)
                // They will be added by doctrine anyway, as they are included in the actions relationship
                // Even adding and outer join caused problems... we'll just have to live with N+1 queries.
            ->where('s.state IN (:states)')
            ->andWhere('t.outboundDate >= :minDate')
            ->andWhere('t.outboundDate < :maxDate')
            ->orderBy('s.surveyPeriodStart', 'ASC')
            ->addOrderBy('v.registrationMark', 'ASC')
            ->addOrderBy('t.outboundDate', 'ASC')
            ->addOrderBy('a.number', 'ASC')
            ->getQuery()
            ->setParameters(new ArrayCollection([
                new Parameter('states', [
                    SurveyStateInterface::STATE_CLOSED,
                    SurveyStateInterface::STATE_APPROVED,
                ]),
                new Parameter('minDate', $minDate),
                new Parameter('maxDate', $maxDate),
            ]))
            ->execute();
    }
}
