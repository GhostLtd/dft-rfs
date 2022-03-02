<?php

namespace App\Repository\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Repository\DashboardStatsTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @method PreEnquiry|null find($id, $lockMode = null, $lockVersion = null)
 * @method PreEnquiry|null findOneBy(array $criteria, array $orderBy = null)
 * @method PreEnquiry[]    findAll()
 * @method PreEnquiry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreEnquiryRepository extends ServiceEntityRepository
{
    use DashboardStatsTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreEnquiry::class);
    }

    public function findLatestSurveyForTesting(): ?PreEnquiry
    {
        try {
            return $this->createQueryBuilder('pe')
                ->orderBy('pe.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new RuntimeException('Query with maxResults 1 returned multiple results!');
        }
    }

    /**
     */
    public function findForExportMonth(int $year, int $month): array
    {
        $monthStart = \DateTimeImmutable::createFromFormat('Y-m', "$year-$month")->modify('midnight first day of this month');
        $monthEnd = $monthStart->modify('+1 month');

        return $this->createQueryBuilder('pe')
            ->select('pe, per, c')
            ->leftJoin('pe.response', 'per')
            ->leftJoin('pe.company', 'c')
            ->where('pe.state = :state')
            ->andWhere('pe.submissionDate >= :monthStart')
            ->andWhere('pe.submissionDate < :monthEnd')
            ->setParameters([
                'state' => PreEnquiry::STATE_CLOSED,
                'monthStart' => $monthStart,
                'monthEnd' => $monthEnd,
            ])
            ->getQuery()
            ->execute();
    }
}
