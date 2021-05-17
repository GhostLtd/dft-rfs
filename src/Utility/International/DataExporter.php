<?php

namespace App\Utility\International;

use App\Repository\International\ActionRepository;
use App\Repository\International\SurveyRepository;
use App\Repository\International\TripRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use App\Serializer\Normalizer\International\SurveysNormalizer;
use App\Serializer\Normalizer\International\TripNormalizer;
use DateTime;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class DataExporter
{
    protected ActionRepository $actionRepository;
    protected SerializerInterface $serializer;
    protected SurveyRepository $surveyRepository;
    protected SurveysNormalizer $surveysNormalizer;
    protected TripRepository $tripRepository;

    public function __construct(
        ActionRepository $actionRepository,
        SqlServerInsertEncoder $sqlServerInsertEncoder,
        SurveysNormalizer $surveysNormalizer,
        SurveyRepository $surveyRepository,
        TripNormalizer $tripNormalizer,
        TripRepository $tripRepository
    )
    {
        $this->actionRepository = $actionRepository;
        $this->surveyRepository = $surveyRepository;
        $this->tripRepository = $tripRepository;
        $this->surveysNormalizer = $surveysNormalizer;

        $this->serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
            $tripNormalizer,
        ], [
            $sqlServerInsertEncoder
        ]);
    }

    public function getDataForExport(int $weekNumber): string {
        $weekStart = WeekNumberHelper::getDate($weekNumber);
        $weekEnd = WeekNumberHelper::getDate($weekNumber + 1);
        $tablePrefix = "irhs-week-{$weekNumber}";

        return
            $this->getTripsSQL($tablePrefix, $weekStart, $weekEnd).
            "\n\n".
            $this->getActionsSQL($tablePrefix, $weekStart, $weekEnd);
    }

    protected function getTripsSQL(string $tablePrefix, DateTime $weekStart, DateTime $weekEnd): string
    {
        $trips = $this->tripRepository->getTripsForExport($weekStart, $weekEnd);
        return $this->serializer->serialize($trips, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "tblIHRVehicleDetails"]);
    }

    protected function getActionsSQL(string $tablePrefix, DateTime $weekStart, DateTime $weekEnd): string
    {
        $surveys = $this->surveyRepository->getSurveysForExport($weekStart, $weekEnd);
        $normalized = $this->surveysNormalizer->normalize($surveys); // N.B. This isn't a Symfony normalizer

        return $this->serializer->serialize($normalized, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "tblIHRConsignmentDetails"]);
    }
}