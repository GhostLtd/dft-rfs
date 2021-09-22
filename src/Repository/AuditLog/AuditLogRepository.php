<?php

namespace App\Repository\AuditLog;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyInterface;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function getApprovalReportStats(string $surveyClass, ?\DateTime $minStart = null, ?\DateTime $maxStart = null, ?bool $isNorthernIreland = null): array
    {
        $addConditions = function(QueryBuilder $queryBuilder) use ($surveyClass, $minStart, $maxStart, $isNorthernIreland) {
            $queryBuilder
                ->andWhere('a.timestamp >= :minStart')
                ->andWhere('a.timestamp < :maxStart')
                ->setParameter('minStart', $minStart)
                ->setParameter('maxStart', $maxStart);
            if ($surveyClass === Survey::class && $isNorthernIreland !== null) {
                $queryBuilder
                    ->andWhere('s.isNorthernIreland = :isNorthernIreland')
                    ->setParameter('isNorthernIreland', $isNorthernIreland);
            }
        };

        // get list of all relevant usernames, for survey/dates
        $userQb = $this->createQueryBuilder('a')
            ->select('a.username')
            ->distinct()
            ->innerJoin($surveyClass, 's', Expr\Join::WITH, 'a.entityId = s.id')
            ->where('a.data LIKE :data')
            ->setParameter('data', '%"to":"' . SurveyInterface::STATE_APPROVED . '"%')
            ->orderBy('a.username', 'ASC')
        ;
        $addConditions($userQb);
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
        $addConditions($approvalsQb);
        $approvalsResults = $approvalsQb->getQuery()->getArrayResult();

        [$firstWeek, $firstYear, $lastWeek, $lastYear] = $this->getWeekAndYearRange($minStart, $maxStart);
        $stats = [];
        for ($year = $firstYear; $year <= $lastYear; $year++) {
            $stats[$year] = ['data' => []];
            for ($week = $firstWeek; $week <= $lastWeek; $week++) {
                $stats[$year]['data'][$week] = [
                    'data' => array_combine($usernames, array_fill(0, count($usernames), 0)),
                ];
            }
        }

        foreach ($approvalsResults as $result) {
            [$week, $year] = WeekNumberHelper::getWeekNumberAndYear($result['timestamp']);
            $approver = $result['username'];

            $stats[$year]['data'][$week]['data'][$approver]++;
        }

        return [$usernames, $stats];
    }

    protected function getWeekAndYearRange(?\DateTime $minDate, ?\DateTime $maxDate): array
    {
        [$firstWeek, $firstYear] = WeekNumberHelper::getWeekNumberAndYear($minDate);
        [$lastWeek, $lastYear] = WeekNumberHelper::getWeekNumberAndYear($maxDate);

        if ($lastWeek === 1) {
            $lastWeek = 53;
            $lastYear--;
        } else {
            $lastWeek--;
        }

        return [$firstWeek, $firstYear, $lastWeek, $lastYear];
    }

}
