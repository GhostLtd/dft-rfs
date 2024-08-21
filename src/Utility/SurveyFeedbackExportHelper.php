<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Feedback;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Serializer\Normalizer\FeedbackExportNormalizer;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SurveyFeedbackExportHelper
{
    protected SerializerInterface|NormalizerInterface $serializer;
    public function __construct(
        protected EntityManagerInterface $entityManager,
        SerializerInterface    $serializer,
    )
    {
        if (!$serializer instanceof NormalizerInterface) {
            throw new \RuntimeException('Passed serializer must implement NormalizerInterface');
        }

        $this->serializer = $serializer;
    }

    public function exportAll(string $exportPath = 'php://output'): void
    {
        $feedback = $this->getFeedbackQueryBuilder()->getQuery()->execute();
        $this->exportFeedback($feedback, $exportPath);
    }

    public function exportExisting(\DateTime $date, $exportPath = 'php://output'): void
    {
        $query = $this->getFeedbackQueryBuilder()
            ->andWhere('f.exportedAt = :exportedAt')
            ->setParameter('exportedAt', $date)
            ->getQuery();

        $feedback = $query->execute();

        if (empty($feedback)) {
            throw new BadRequestHttpException('no feedback responses found for given date');
        }

        $this->exportFeedback($feedback, $exportPath);
    }

    public function exportNew(\DateTime $exportDate, $exportPath = 'php://output'): void
    {
        // get the ones with no exported date
        $query = $this->getFeedbackQueryBuilder()
            ->andWhere('f.exportedAt IS NULL')
            ->getQuery();
        $feedback = $query->execute();

        array_map(fn(Feedback $f) => $f->setExportedAt($exportDate), $feedback);
        $this->entityManager->flush();

        // export
        $this->exportFeedback($feedback, $exportPath);
    }

    /* ---------------------- */

    /**
     * @throws ExceptionInterface
     */
    protected function exportFeedback(array $feedback, string $exportPath): void
    {
        $feedbackIds = $this->getIds($feedback);
        $allNormalizedFeedback = [];
        foreach ([DomesticSurvey::class, InternationalSurvey::class, PreEnquiry::class] as $surveyClass)
        {
            $surveyFeedback = $this->getSurveyQueryBuilder($surveyClass, $feedbackIds)->getQuery()->execute();
            $surveyFeedback = $this->serializer->normalize($surveyFeedback, 'csv', [FeedbackExportNormalizer::CONTEXT_KEY => true]);
            $allNormalizedFeedback = array_merge($allNormalizedFeedback, $surveyFeedback);
        }
        $handle = fopen($exportPath, 'w');
        fputs($handle, $this->serializer->serialize($allNormalizedFeedback, 'csv'));
        fclose($handle);
    }

    /* ---------------------- */

    protected function getIds(array $entities): array
    {
        return array_map(fn($entity) => $entity->getId(), $entities);
    }

    protected function getSurveyQueryBuilder(string $surveyClass, array $feedbackIds): QueryBuilder
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('s, f')
            ->from($surveyClass, 's')
            ->join('s.feedback', 'f', Expr\Join::WITH, 'f.id in (:feedbackIds)')
            ->setParameter('feedbackIds', $feedbackIds)
        ;
    }

    public function getPastExportDates(): array
    {
        $query = $this->getFeedbackQueryBuilder()
            ->select('
                    f.exportedAt
                ')
            ->andWhere('f.exportedAt IS NOT NULL')
            ->groupBy('f.exportedAt')
            ->orderBy('f.exportedAt', 'DESC')
            ->getQuery();
        $results = $query->execute();
        return array_map(fn($v) => ($v['exportedAt'] ?? $v), $results);
    }

    public function hasAnyFeedbackReadyForNewExport(): bool
    {
        // get the ones with no exported date
        $query = $this->getFeedbackQueryBuilder()
            ->andWhere('f.exportedAt IS NULL')
            ->getQuery();
        $feedback = $query->execute();
        return !empty($feedback);
    }

    protected function getFeedbackQueryBuilder(): QueryBuilder
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('f')
            ->from(Feedback::class, 'f')
        ;
    }
}
