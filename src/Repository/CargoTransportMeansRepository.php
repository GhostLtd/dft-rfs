<?php

namespace App\Repository;

use App\Entity\CargoTransportMeans;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CargoTransportMeans|null find($id, $lockMode = null, $lockVersion = null)
 * @method CargoTransportMeans|null findOneBy(array $criteria, array $orderBy = null)
 * @method CargoTransportMeans[]    findAll()
 * @method CargoTransportMeans[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CargoTransportMeansRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CargoTransportMeans::class);
    }

    // /**
    //  * @return CargoTransportMeans[] Returns an array of CargoTransportMeans objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CargoTransportMeans
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
