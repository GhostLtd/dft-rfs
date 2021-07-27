<?php

namespace App\Utility\International;

use App\Entity\International\Survey;
use App\Entity\International\Trip;
use App\Repository\International\SurveyRepository;
use App\Repository\International\TripRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use App\Serializer\Normalizer\International\SurveyActionsNormalizer;
use App\Serializer\Normalizer\International\TripNormalizer;
use App\Utility\Export\AbstractDataExporter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class DataExporter extends AbstractDataExporter
{
    protected SerializerInterface $serializer;
    protected SurveyRepository $surveyRepository;
    protected SurveyActionsNormalizer $surveyActionsNormalizer;
    protected TripRepository $tripRepository;
    protected ExportHelper $exportHelper;
    protected LoggerInterface $logger;

    public function __construct(
        SqlServerInsertEncoder $sqlServerInsertEncoder,
        SurveyActionsNormalizer $surveyActionsNormalizer,
        TripNormalizer $tripNormalizer,
        WorkflowInterface $internationalSurveyStateMachine,
        EntityManagerInterface $entityManager,
        ExportHelper $exportHelper,
        LoggerInterface $logger
    )
    {
        parent::__construct($internationalSurveyStateMachine, $entityManager);

        $this->surveyRepository = $entityManager->getRepository(Survey::class);
        $this->tripRepository = $entityManager->getRepository(Trip::class);
        $this->surveyActionsNormalizer = $surveyActionsNormalizer;

        $this->serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
            $tripNormalizer,
        ], [
            $sqlServerInsertEncoder
        ]);
        $this->exportHelper = $exportHelper;
        $this->logger = $logger;
    }

    public function uploadExportData(int $weekNumber): bool {
        $weekStart = WeekNumberHelper::getDate($weekNumber);
        $weekEnd = WeekNumberHelper::getDate($weekNumber + 1);
        $surveys = $this->surveyRepository->getSurveysForExport($weekStart, $weekEnd);

        try {
            $this->startExport($surveys);
            $sql = $this->getTripsSQL($surveys).
                "\n\n".
                $this->getActionsSQL($surveys);
            $this->exportHelper->upload($sql, $weekNumber);
            $this->confirmExport($surveys);
            return true;
        } catch (\Throwable $e) {
            $this->cancelExport($surveys);
            $this->logger->error("[DataExporter] Export generation/upload failed", ['exception' => $e]);
            return false;
        }
    }

    protected function getTripsSQL($surveys): string
    {
        $trips = $this->tripRepository->getTripsForExport($surveys);
        return $this->serializer->serialize($trips, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "tblIHRVehicleDetails"]);
    }

    protected function getActionsSQL($surveys): string
    {
        $normalized = $this->surveyActionsNormalizer->normalize($surveys); // N.B. This isn't a Symfony normalizer
        return $this->serializer->serialize($normalized, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "tblIHRConsignmentDetails"]);
    }
}