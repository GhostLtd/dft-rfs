<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\DriverAvailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DriverAvailability|null find($id, $lockMode = null, $lockVersion = null)
 * @method DriverAvailability|null findOneBy(array $criteria, array $orderBy = null)
 * @method DriverAvailability[]    findAll()
 * @method DriverAvailability[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DriverAvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DriverAvailability::class);
    }

    // /**
    //  * @return DriverAvailability[] Returns an array of DriverAvailability objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DriverAvailability
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
