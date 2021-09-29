<?php

namespace App\Repository\AuditLog;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use App\Utility\International\WeekNumberHelper as InternationalWeekNumberHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnexpectedResultException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function surveyHasPreviouslyBeenInClosedState(SurveyInterface $entity): bool
    {
        $transitions = $this->createQueryBuilder('audit_log')
            ->select('audit_log')
            ->where('audit_log.entityId = :id')
            ->andWhere('audit_log.entityClass = :class')
            ->andWhere('audit_log.data LIKE :props')
            ->setParameters([
                'id' => $entity->getId(),
                'class' => get_class($entity),
                'props' => '%"' . SurveyInterface::STATE_CLOSED . '"%',
            ])
            ->orderBy('audit_log.timestamp', 'desc')
            ->getQuery()
            ->execute();
        return count($transitions) > 0;
    }

    public function getApprovedBy($entity)
    {
        return $this->createQueryBuilder('audit_log')
            ->select('audit_log.username, audit_log.timestamp')
            ->where('audit_log.entityId = :id')
            ->andWhere('audit_log.entityClass = :class')
            ->andWhere('audit_log.data LIKE :props')
            ->setParameters([
                'id' => $entity->getId(),
                'class' => get_class($entity),
                'props' => '%"to":"' . SurveyInterface::STATE_APPROVED . '"%',
            ])
            ->orderBy('audit_log.timestamp', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function getLogs(string $entityId, string $entityClass): array
    {
        return $this
            ->createQueryBuilder('l')
            ->where('l.entityClass = :entityClass')
            ->andWhere('l.entityId = :entityId')
            ->orderBy('l.timestamp', 'DESC')
            ->getQuery()
            ->setParameters([
                'entityId' => $entityId,
                'entityClass' => $entityClass,
            ])
            ->execute();
    }

    public function getQualityAssuredBy($entity)
    {
        return $this->createQueryBuilder('audit_log')
            ->select('audit_log.username, audit_log.timestamp')
            ->where('audit_log.entityId = :id')
            ->andWhere('audit_log.entityClass = :class')
            ->andWhere('audit_log.category = :category')
            ->setParameters([
                'id' => $entity->getId(),
                'class' => get_class($entity),
                'category' => 'survey-qa',
            ])
            ->orderBy('audit_log.timestamp', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    protected function addApprovalQueryBuilderConditions(QueryBuilder $queryBuilder, string $surveyClass, ?\DateTime $minStart = null, ?\DateTime $maxStart = null, ?bool $isNorthernIreland = null)
    {
        $queryBuilder
            ->andWhere('a.timestamp >= :minStart')
            ->andWhere('a.timestamp < :maxStart')
            ->setParameter('minStart', $minStart)
            ->setParameter('maxStart', $maxStart);
        if ($surveyClass === DomesticSurvey::class && $isNorthernIreland !== null) {
            $queryBuilder
                ->andWhere('s.isNorthernIreland = :isNorthernIreland')
                ->setParameter('isNorthernIreland', $isNorthernIreland);
        }
    }

    protected function getApprovalUsernamesAndData(string $surveyClass, ?\DateTime $minStart = null, ?\DateTime $maxStart = null, ?bool $isNorthernIreland = null): array
    {
        // get list of all relevant usernames, for survey/dates
        $userQb = $this->createQueryBuilder('a')
            ->select('a.username')
            ->distinct()
            ->innerJoin($surveyClass, 's', Expr\Join::WITH, 'a.entityId = s.id')
            ->where('a.data LIKE :data')
            ->setParameter('data', '%"to":"' . SurveyInterface::STATE_APPROVED . '"%')
            ->orderBy('a.username', 'ASC')
        ;
        $this->addApprovalQueryBuilderConditions($userQb, $surveyClass, $minStart, $maxStart, $isNorthernIreland);
        $usernames = $userQb->getQuery()->getArrayResult();
        $usernames = array_map(fn($u) => $u['username'], $usernames);
        if (!$usernames) {
            $usernames = ['-'];
        }

        $approvalsQb = $this->createQueryBuilder('a')
            ->select('a.username, a.timestamp')
            ->innerJoin($surveyClass, 's', Expr\Join::WITH, 'a.entityId = s.id')
            ->where('a.data LIKE :data')
            ->setParameter('data', '%"to":"' . SurveyInterface::STATE_APPROVED . '"%')
            ->orderBy('a.timestamp', 'ASC')
        ;
        $this->addApprovalQueryBuilderConditions($approvalsQb, $surveyClass, $minStart, $maxStart, $isNorthernIreland);
        $approvalsResults = $approvalsQb->getQuery()->getArrayResult();

        return [$usernames, $approvalsResults];
    }

    /**
     * @throws UnexpectedResultException
     */
    public function getDomesticApprovalReportStats(?\DateTime $minStart = null, ?\DateTime $maxStart = null, ?bool $isNorthernIreland = null): array
    {
        [$usernames, $approvalsResults] = $this->getApprovalUsernamesAndData(DomesticSurvey::class, $minStart, $maxStart, $isNorthernIreland);

        [$firstWeek, $lastWeek] = $this->getDomesticWeekRange($minStart, $maxStart);
        $stats = [
            'usernames' => $usernames,
            'data' => $this->getEmptyApprovalQuarterData($usernames, $firstWeek, $lastWeek)
        ];

        foreach ($approvalsResults as $result) {
            [$week, $year] = DomesticWeekNumberHelper::getYearlyWeekNumberAndYear($result['timestamp']);
            $approver = $result['username'];
            $stats['data'][$week]['data'][$approver]++;
        }

        return $stats;
    }

    public function getInternationalApprovalReportStats(?\DateTime $minStart = null, ?\DateTime $maxStart = null): array
    {
        [$usernames, $approvalsResults] = $this->getApprovalUsernamesAndData(InternationalSurvey::class, $minStart, $maxStart);

        $firstWeek = InternationalWeekNumberHelper::getWeekNumber($minStart);
        $lastWeek = InternationalWeekNumberHelper::getWeekNumber($maxStart) - 1;
        $stats = [
            'usernames' => $usernames,
            'data' => $this->getEmptyApprovalQuarterData($usernames, $firstWeek, $lastWeek)
        ];

        foreach ($approvalsResults as $result) {
            $week = InternationalWeekNumberHelper::getWeekNumber($result['timestamp']);
            $approver = $result['username'];
            $stats['data'][$week]['data'][$approver]++;
        }

        return $stats;
    }

    protected function getEmptyApprovalQuarterData(array $usernames, $firstWeek, $lastWeek): array
    {
        $emptyApprovalReportData = [];
        for ($week = $firstWeek; $week <= $lastWeek; $week++) {
            $emptyApprovalReportData[$week] = [
                'data' => array_combine($usernames, array_fill(0, count($usernames), 0)),
            ];
        }
        return $emptyApprovalReportData;
    }

    /**
     * @throws UnexpectedResultException
     */
    protected function getDomesticWeekRange(?\DateTime $minDate, ?\DateTime $maxDate): array
    {
        [$firstWeek, $firstYear] = DomesticWeekNumberHelper::getYearlyWeekNumberAndYear($minDate);
        [$lastWeek, $lastYear] = DomesticWeekNumberHelper::getYearlyWeekNumberAndYear($maxDate);

        if ($lastWeek === 1) {
            $lastWeek = 53;
            $lastYear--;
        } else {
            $lastWeek--;
        }

        if ($lastYear !== $firstYear) {
            throw new UnexpectedResultException('Query meant for quarterly results. Years should match.');
        }

        return [$firstWeek, $lastWeek];
    }

}
