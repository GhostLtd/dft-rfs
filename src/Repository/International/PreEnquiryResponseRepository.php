<?php

namespace App\Repository\International;

use App\Entity\International\PreEnquiryResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PreEnquiryResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method PreEnquiryResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method PreEnquiryResponse[]    findAll()
 * @method PreEnquiryResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreEnquiryResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreEnquiryResponse::class);
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
