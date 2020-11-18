<?php

namespace App\Repository;

use App\Entity\InternationalPreEnquiry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InternationalPreEnquiry|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternationalPreEnquiry|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternationalPreEnquiry[]    findAll()
 * @method InternationalPreEnquiry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternationalPreEnquiryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternationalPreEnquiry::class);
    }

    public function findLatestSurveyForTesting(): ?InternationalPreEnquiry
    {
        return $this->createQueryBuilder('pe')
            ->orderBy('pe.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }
}
