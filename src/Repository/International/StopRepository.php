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
            ->setParameters([
                'trip' => $consignment->getTrip(),
                'minStop' => $minStop,
            ])
            ->getQuery()
            ->execute();
    }

    // /**
    //  * @return InternationalStop[] Returns an array of InternationalStop objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InternationalStop
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
