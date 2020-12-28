<?php

namespace App\Repository\International;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
                ->select('t,v,r,s,c,cls,cus')
                ->leftJoin('t.vehicle', 'v')
                ->leftJoin('v.surveyResponse', 'r')
                ->leftJoin('t.stops', 's')
                ->leftJoin('t.consignments', 'c')
                ->leftJoin('c.loadingStop', 'cls')
                ->leftJoin('c.unloadingStop', 'cus')
                ->where('t.id = :id')
                ->andWhere('r = :response')
                ->orderBy('s.number', 'ASC')
                ->addOrderBy('cls.number', 'ASC')
                ->addOrderBy('cus.number', 'ASC')
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
}
