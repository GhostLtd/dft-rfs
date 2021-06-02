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
    private ExportHelper $exportHelper;

    public function __construct(
        SqlServerInsertEncoder $sqlServerInsertEncoder,
        SurveyActionsNormalizer $surveyActionsNormalizer,
        TripNormalizer $tripNormalizer,
        WorkflowInterface $internationalSurveyStateMachine,
        EntityManagerInterface $entityManager,
        ExportHelper $exportHelper
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
    }

    public function getDataForExport(int $weekNumber): ?string {
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
            return $sql;
        } catch (\Throwable $e) {
            $this->cancelExport($surveys);
            // Todo: log exception
            return null;
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