<?php

namespace App\Repository;

use App\Entity\InternationalPreEnquiryResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InternationalPreEnquiryResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternationalPreEnquiryResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternationalPreEnquiryResponse[]    findAll()
 * @method InternationalPreEnquiryResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternationalPreEnquiryResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternationalPreEnquiryResponse::class);
    }

    // /**
    //  * @return InternationalPreEnquiryResponse[] Returns an array of InternationalPreEnquiryResponse objects
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
    public function findOneBySomeField($value): ?InternationalPreEnquiryResponse
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
