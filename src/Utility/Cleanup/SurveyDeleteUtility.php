<?php

namespace App\Utility\Cleanup;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use App\Repository\SurveyDeletionInterface;
use App\Utility\Domestic\DeleteHelper as DomesticDeleteHelper;
use App\Utility\International\DeleteHelper as InternationalDeleteHelper;
use App\Utility\PreEnquiry\DeleteHelper as PreEnquiryDeleteHelper;
use App\Utility\RoRo\DeleteHelper as RoRoDeleteHelper;
use App\Utility\Quarter\QuarterHelperProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

class SurveyDeleteUtility
{
    // Clear surveys older than eight quarters (2 years)
    protected const int CLEANUP_OFFSET = -8;

    protected const array CLEANUP_SURVEY_CLASSES = [
        DomesticSurvey::class,
        InternationalSurvey::class,
        PreEnquiry::class,
        RoRoSurvey::class,
    ];

    protected \DateTime $currentDate;

    public function __construct(
        protected EntityManagerInterface    $entityManager,
        protected LoggerInterface           $logger,
        protected QuarterHelperProvider     $quarterHelperProvider,

        protected DomesticDeleteHelper      $domesticDeleteHelper,
        protected InternationalDeleteHelper $internationalDeleteHelper,
        protected PreEnquiryDeleteHelper    $preEnquiryDeleteHelper,
        protected RoRoDeleteHelper          $roroDeleteHelper,
    ) {
        $this->currentDate = new \DateTime();
    }

    public function setCurrentDateForTesting(\DateTime $currentDate): SurveyDeleteUtility
    {
        $this->currentDate = $currentDate;
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function deleteOldSurveys(): int
    {
        $total = 0;

        $module = "Delete old surveys";
        $this->logger->notice("[{$module}] Started");

        foreach (self::CLEANUP_SURVEY_CLASSES as $surveyClass) {
            try {
                $count = 0;

                $this->entityManager->beginTransaction();

                $quarterHelper = $this->quarterHelperProvider->getQuarterHelperForDataCleanupBySurveyClass($surveyClass);
                $repo = $this->getRepositoryForSurveyClass($surveyClass);

                $before = $quarterHelper->getStartOfQuarterOffset(self::CLEANUP_OFFSET, $this->currentDate);
                $matchingSurveys = $repo->getSurveysForDeletion($before);

                $matchingSurveyIds = array_map(fn(SurveyInterface|RoRoSurvey $x) => $x->getId(), $matchingSurveys);

                $this->deleteAuditLogEntriesFor($surveyClass, $matchingSurveyIds);

                foreach ($matchingSurveys as $survey) {
                    $this->deleteSurvey($survey, false);
                    $count++;
                }

                $this->entityManager->flush();
                $this->entityManager->commit();

                $total += $count;
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->logger->error("[{$module}] Failure whilst trying to delete surveys of type {$surveyClass}");
                $total > 0 && $this->logger->notice("[{$module}] Deleted a total of {$total} surveys");
                throw $e;
            }

            $count > 0 && $this->logger->notice("[{$module}] Deleted {$count} surveys of type {$surveyClass}");
        }

        $total > 0 && $this->logger->notice("[{$module}] Deleted a total of {$total} surveys");
        $this->logger->notice("[{$module}] Completed successfully" .
            ($total === 0 ? "; nothing to delete" : "")
        );

        return $total;
    }

    public function getRepositoryForSurveyClass(string $surveyClass): EntityRepository&SurveyDeletionInterface
    {
        $repo = $this->entityManager->getRepository($surveyClass);

        if (!$repo instanceof SurveyDeletionInterface) {
            throw new \RuntimeException("Repository for {$surveyClass} does not implement SurveyDeletionInterface");
        }

        return $repo;
    }

    public function deleteSurvey(object $survey, bool $flush = true): void
    {
        // N.B. match() is not a good replacement due to the possibility of proxies.
        //      Using ClassUtils::getRealClass() leads to PHPStorm not knowing that the type of $survey is correct.
        if ($survey instanceof DomesticSurvey) {
            $this->domesticDeleteHelper->deleteSurvey($survey, $flush);
        } else if ($survey instanceof InternationalSurvey) {
            $this->internationalDeleteHelper->deleteSurvey($survey, $flush);
        } else if ($survey instanceof PreEnquiry) {
            $this->preEnquiryDeleteHelper->deleteSurvey($survey, $flush);
        } else if ($survey instanceof RoRoSurvey) {
            $this->roroDeleteHelper->deleteSurvey($survey, $flush);
        } else {
            throw new \RuntimeException('Unsupported survey class - ' . $survey::class);
        }
    }

    public function deleteAuditLogEntriesFor(string $entityClass, array $entityIds): void
    {
        $this->entityManager
            ->createQueryBuilder()
            ->delete(AuditLog::class, 'a')
            ->where('a.entityClass = :class')
            ->andWhere('a.entityId IN (:ids)')
            ->setParameter('class', $entityClass)
            ->setParameter('ids', $entityIds)
            ->getQuery()
            ->execute();
    }
}
