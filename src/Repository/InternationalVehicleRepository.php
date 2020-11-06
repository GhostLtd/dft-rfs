<?php

namespace App\Repository;

use App\Entity\InternationalVehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InternationalVehicle|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternationalVehicle|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternationalVehicle[]    findAll()
 * @method InternationalVehicle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternationalVehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternationalVehicle::class);
    }

    // /**
    //  * @return InternationalVehicle[] Returns an array of InternationalVehicle objects
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
    public function findOneBySomeField($value): ?InternationalVehicle
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