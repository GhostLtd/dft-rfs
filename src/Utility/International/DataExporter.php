<?php

namespace App\Utility\International;

use App\Repository\International\TripRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

class DataExporter
{
    protected TripRepository $tripRepository;
    protected SqlServerInsertEncoder $sqlServerInsertEncoder;
    protected TripNormalizer $tripNormalizer;
    protected WeekNumberHelper $weekNumberHelper;

    public function __construct(TripRepository $tripRepository,
                                SqlServerInsertEncoder $sqlServerInsertEncoder,
                                TripNormalizer $tripNormalizer,
                                WeekNumberHelper $weekNumberHelper
    )
    {
        $this->tripRepository = $tripRepository;
        $this->sqlServerInsertEncoder = $sqlServerInsertEncoder;
        $this->tripNormalizer = $tripNormalizer;
        $this->weekNumberHelper = $weekNumberHelper;
    }

    public function getDataForExport(int $weekNumber) {
        $weekStart = $this->weekNumberHelper->getDate($weekNumber);
        $weekEnd = $this->weekNumberHelper->getDate($weekNumber + 1);
        $trips = $this->tripRepository->getTripsForExport($weekStart, $weekEnd);

        $serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
            $this->tripNormalizer,
        ], [
            $this->sqlServerInsertEncoder,
        ]);

        dump($serializer->normalize($trips)); // TODO: Remove

        return $serializer->serialize($trips, 'sql-server-insert');
    }
}