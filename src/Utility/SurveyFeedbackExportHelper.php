<?php

namespace App\Utility;

use App\Entity\Feedback;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class SurveyFeedbackExportHelper
{
    protected EntityManagerInterface $entityManager;
    protected SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function exportAll(string $exportPath = 'php://output')
    {
        $feedback = $this->getQueryBuilder()->getQuery()->execute();
        $this->exportFeedback($feedback, $exportPath);
    }

    public function exportExisting(\DateTime $date, $exportPath = 'php://output')
    {
        $query = $this->getQueryBuilder()
            ->andWhere('f.exportedAt = :exportedAt')
            ->setParameter('exportedAt', $date)
            ->getQuery();

        $feedback = $query->execute();

        if (empty($feedback)) {
            throw new BadRequestHttpException('no feedback responses found for given date');
        }

        $this->exportFeedback($feedback, $exportPath);
    }

    public function exportNew(\DateTime $exportDate, $exportPath = 'php://output')
    {
        // get the ones with no exported date
        $query = $this->getQueryBuilder()
            ->andWhere('f.exportedAt IS NULL')
            ->getQuery();
        $feedback = $query->execute();

        $exportDate = new \DateTime();
        array_map(fn(Feedback $f) => $f->setExportedAt($exportDate), $feedback);
        $this->entityManager->flush();

        // export
        $this->exportFeedback($feedback, $exportPath);
    }

    protected function exportFeedback($feedback, $exportPath)
    {
        $handle = fopen($exportPath, 'w');
        fputs($handle, $this->serializer->serialize($feedback, 'csv'));
        fclose($handle);
    }

    public function getExistingDates(): array
    {
        $query = $this->getQueryBuilder()
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
        $query = $this->getQueryBuilder()
            ->andWhere('f.exportedAt IS NULL')
            ->getQuery();
        $feedback = $query->execute();
        return !empty($feedback);
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('f')
            ->from(Feedback::class, 'f')
        ;
    }

    public function getHeaders(array $feedback): array
    {
        return array_map(function ($name) {
            $parts = preg_split('/(?=[A-Z])/', $name);
            return ucfirst(join(' ', array_map("strtolower", $parts)));
        }, array_keys(current($feedback)));
    }
}