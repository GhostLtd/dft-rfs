<?php

namespace App\Repository\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyStateInterface;
use App\Repository\DashboardStatsTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    /**
     */
    public function findForExportMonth(int $year, int $month): array
    {
        $monthStart = \DateTimeImmutable::createFromFormat('Y-m-d', "$year-$month-1")->modify('midnight first day of this month');
        $monthEnd = $monthStart->modify('+1 month');

        return $this->createQueryBuilder('pe')
            ->select('pe, per')
            ->leftJoin('pe.response', 'per')
            ->where('pe.state IN (:states)')
            ->andWhere('pe.submissionDate >= :monthStart')
            ->andWhere('pe.submissionDate < :monthEnd')
            ->setParameters([
                'states' => [SurveyStateInterface::STATE_CLOSED, SurveyStateInterface::STATE_APPROVED],
                'monthStart' => $monthStart,
                'monthEnd' => $monthEnd,
            ])
            ->getQuery()
            ->execute();
    }

    public function getOverdueCount(): int
    {
        return $this->createQueryBuilder('p')
            ->select('count(p) AS count')
            ->where('p.state IN (:states)')
            ->andWhere('p.invitationSentDate < :fourteenDaysAgo')
            ->setParameters([
                'states' => SurveyStateInterface::ACTIVE_STATES,
                'fourteenDaysAgo' => (new \DateTime())->modify('-14 days'),
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInProgressCount(): int
    {
        return $this->createQueryBuilder('p')
            ->select('count(p) AS count')
            ->where('p.state IN (:states)')
            ->setParameters([
                'states' => SurveyStateInterface::ACTIVE_STATES,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
