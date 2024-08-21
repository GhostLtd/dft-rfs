<?php

namespace App\Utility\Cleanup;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use App\Utility\Quarter\QuarterHelperProvider;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PersonalDataCleanupUtility
{
    // Clear personal data from surveys older than four quarters (1 year)
    protected const int CLEANUP_OFFSET = -4;

    protected const array CLEANUP_SURVEY_CLASSES = [
        DomesticSurvey::class,
        InternationalSurvey::class,
    ];

    public const string CATEGORY = 'cleanup-personal';

    protected \DateTime $currentDate;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface        $logger,
        protected QuarterHelperProvider  $quarterHelperProvider,
    ) {
        $this->currentDate = new \DateTime();
    }

    public function setCurrentDateForTesting(\DateTime $currentDate): PersonalDataCleanupUtility
    {
        $this->currentDate = $currentDate;
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function cleanupPersonalData(): int
    {
        $total = 0;

        $module = "Cleanup personal data";
        $this->logger->notice("[{$module}] Started");

        foreach (self::CLEANUP_SURVEY_CLASSES as $surveyClass) {
            try {
                $count = 0;

                $this->entityManager->beginTransaction();

                $quarterHelper = $this->quarterHelperProvider->getQuarterHelperForDataCleanupBySurveyClass($surveyClass);
                $before = $quarterHelper->getStartOfQuarterOffset(self::CLEANUP_OFFSET, $this->currentDate);

                $matchingSurveys = $this->getSurveysRequiringPersonalDataCleanup($before, $surveyClass);

                foreach ($matchingSurveys as $survey) {
                    $survey->clearPersonalData();
                    $this->entityManager->persist($this->getAuditLogEntry($survey));
                    $count++;
                }

                $this->entityManager->flush();
                $this->entityManager->commit();

                $total += $count;
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->logger->error("[{$module}] Failure whilst trying to clear personal data for surveys of type {$surveyClass}");
                $total > 0 && $this->logger->notice("[{$module}] Deleted a total of {$total} surveys");
                throw $e;
            }

            $count > 0 && $this->logger->notice("[{$module}] Cleared personal data for {$count} surveys of type {$surveyClass}");
        }

        $total > 0 && $this->logger->notice("[{$module}] Cleared personal data for a total of {$total} surveys");
        $this->logger->notice("[{$module}] Completed successfully" .
            ($total === 0 ? "; nothing to clear" : "")
        );

        return $total;
    }

    protected function getAuditLogEntry(SurveyInterface $survey): AuditLog
    {
        $class = ClassUtils::getClass($survey);

        return (new AuditLog())
            ->setCategory(self::CATEGORY)
            ->setUsername('-')
            ->setEntityId($survey->getId())
            ->setEntityClass($class)
            ->setTimestamp(new \DateTime())
            ->setData([]);
    }

    /**
     * @return array<PersonalDataCleanupInterface&SurveyInterface>
     */
    public function getSurveysRequiringPersonalDataCleanup(\DateTime $before, string $entityClass): array
    {
        $auditLog = AuditLog::class;

        $dql = <<<EOQ
SELECT s
FROM {$entityClass} s 
WHERE s.surveyPeriodEnd < :beforeDate
AND NOT EXISTS (
    SELECT a.id FROM {$auditLog} a 
    WHERE a.category = :category
    AND a.entityId = s.id
    AND a.entityClass = :class
)
EOQ;

        $q = $this->entityManager
            ->createQuery($dql)
            ->setParameter('beforeDate', $before->format('Y-m-d'))
            ->setParameter('category', self::CATEGORY)
            ->setParameter('class', $entityClass);

        return $q->execute();
    }
}
