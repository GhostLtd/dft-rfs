<?php

namespace App\Utility\International;

use App\Repository\International\ActionRepository;
use App\Repository\International\TripRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use App\Serializer\Normalizer\International\ActionNormalizer;
use App\Serializer\Normalizer\International\TripNormalizer;
use DateTime;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class DataExporter
{
    protected ActionRepository $actionRepository;
    protected TripRepository $tripRepository;
    protected SerializerInterface $serializer;

    public function __construct(
        ActionNormalizer $actionNormalizer,
        ActionRepository $actionRepository,
        SqlServerInsertEncoder $sqlServerInsertEncoder,
        TripNormalizer $tripNormalizer,
        TripRepository $tripRepository
    )
    {
        $this->actionRepository = $actionRepository;
        $this->tripRepository = $tripRepository;

        $this->serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
            $actionNormalizer,
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
        return $this->serializer->serialize($trips, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "{$tablePrefix}-vehicle"]);
    }

    protected function getActionsSQL(string $tablePrefix, DateTime $weekStart, DateTime $weekEnd): string
    {
        $actions = $this->actionRepository->getActionsForExport($weekStart, $weekEnd);
        dump($actions);
        return $this->serializer->serialize($actions, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "{$tablePrefix}-consignment"]);
    }
}