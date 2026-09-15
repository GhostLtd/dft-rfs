<?php

namespace App\Repository\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryNotification;
use App\Entity\SurveyStateInterface;
use App\Repository\DashboardStatsTrait;
use App\Repository\SurveyDeletionInterface;
use App\Utility\Notification\NotificationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PreEnquiry|null find($id, $lockMode = null, $lockVersion = null)
 * @method PreEnquiry|null findOneBy(array $criteria, array $orderBy = null)
 * @method PreEnquiry[]    findAll()
 * @method PreEnquiry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreEnquiryRepository extends ServiceEntityRepository implements SurveyDeletionInterface
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
            ->setParameters(new ArrayCollection([
                new Parameter('states', [SurveyStateInterface::STATE_CLOSED]),
                new Parameter('monthStart', $monthStart),
                new Parameter('monthEnd', $monthEnd),
            ]))
            ->getQuery()
            ->execute();
    }

    public function getOverdueCount(): int
    {
        // The legacy sent-date column this replaced held the *latest* invite date (it was overwritten on
        // resend), so "overdue" means the latest invite is older than 14 days - not merely that some invite,
        // however long ago, was. Hence: an invite exists, and none of them was sent in the last 14 days.
        $invited = $this->getEntityManager()->createQueryBuilder()
            ->select('n.id')
            ->from(PreEnquiryNotification::class, 'n')
            ->where('n.survey = p')
            ->andWhere('n.type = :invite')
            ->getDQL();

        $invitedRecently = $this->getEntityManager()->createQueryBuilder()
            ->select('n2.id')
            ->from(PreEnquiryNotification::class, 'n2')
            ->where('n2.survey = p')
            ->andWhere('n2.type = :invite')
            ->andWhere('n2.sentAt >= :fourteenDaysAgo')
            ->getDQL();

        return (int) $this->createQueryBuilder('p')
            ->select('count(p) AS count')
            ->where('p.state IN (:states)')
            ->andWhere("EXISTS ({$invited})")
            ->andWhere("NOT EXISTS ({$invitedRecently})")
            ->setParameters(new ArrayCollection([
                new Parameter('states', SurveyStateInterface::ACTIVE_STATES),
                new Parameter('invite', NotificationType::INVITE->value),
                new Parameter('fourteenDaysAgo', (new \DateTime())->modify('-14 days')),
            ]))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInProgressCount(): int
    {
        return $this->createQueryBuilder('p')
            ->select('count(p) AS count')
            ->where('p.state IN (:states)')
            ->setParameter('states', SurveyStateInterface::ACTIVE_STATES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPreEnquiryIdsByCompanyName(string $businessName): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('p.id')
            ->where('UPPER(TRIM(p.companyName)) = :companyName')
            ->setParameter('companyName', mb_strtoupper($businessName))
            ->getQuery()
            ->getArrayResult();

        return array_map(fn(array $a) => $a['id'], $results);
    }

    public function getSurveysForDeletion(\DateTime $before): array
    {
        return $this->createQueryBuilder('pre')
            ->select('pre, response')
            ->where('pre.dispatchDate < :before')
            ->leftJoin('pre.response', 'response')
            ->setParameter('before', $before->format('Y-m-d'))
            ->getQuery()
            ->execute();
    }
}
