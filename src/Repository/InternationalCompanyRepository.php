<?php

namespace App\Repository;

use App\Entity\InternationalCompany;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InternationalCompany|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternationalCompany|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternationalCompany[]    findAll()
 * @method InternationalCompany[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternationalCompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternationalCompany::class);
    }

    // /**
    //  * @return InternationalCompany[] Returns an array of InternationalCompany objects
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
    public function findOneBySomeField($value): ?InternationalCompany
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
