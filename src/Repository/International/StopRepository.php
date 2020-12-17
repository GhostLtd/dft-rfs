<?php

namespace App\Repository\International;

use App\Entity\International\Consignment;
use App\Entity\International\Stop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stop|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stop|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stop[]    findAll()
 * @method Stop[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stop::class);
    }

    public function getStopsForConsignment(Consignment $consignment, $minStop = 0)
    {
        $qb = $this->createQueryBuilder('stop');
        return $qb
            ->join('stop.trip', 'trip')
            ->andWhere('trip = :trip')
            ->andWhere('stop.number >= :minStop')
            ->orderBy('stop.number', 'ASC')
            ->setParameters([
                'trip' => $consignment->getTrip(),
                'minStop' => $minStop,
            ])
            ->getQuery()
            ->execute();
    }
}
