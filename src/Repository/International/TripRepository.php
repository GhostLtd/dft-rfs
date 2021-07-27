<?php

namespace App\Repository\International;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
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
                ->setParameters([
                    'id' => $id,
                    'response' => $response,
                ])
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
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
                ->setParameters([
                    'id' => $id,
                    'response' => $response,
                ])
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @return Trip[]|Collection
     */
    public function getTripsForExport($surveys): array
    {
        return $this->createQueryBuilder('t')
            ->select('t,v,r,s')
            ->leftJoin('t.vehicle', 'v')
            ->leftJoin('v.surveyResponse', 'r')
            ->leftJoin('r.survey', 's')
            ->andWhere('s IN (:surveys)')
            ->getQuery()
            ->setParameters([
                'surveys' => $surveys,
            ])
            ->execute();
    }
}
