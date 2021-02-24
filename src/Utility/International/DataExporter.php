<?php

namespace App\Utility\International;

use App\Repository\International\VehicleRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

class DataExporter
{
    protected VehicleRepository $vehicleRepository;
    protected SqlServerInsertEncoder $sqlServerInsertEncoder;

    public function __construct(VehicleRepository $vehicleRepository, SqlServerInsertEncoder $sqlServerInsertEncoder)
    {
        $this->vehicleRepository = $vehicleRepository;
        $this->sqlServerInsertEncoder = $sqlServerInsertEncoder;
    }

    public function getDataForExport(int $week) {
        $serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
        ], [
            $this->sqlServerInsertEncoder,
        ]);
        return $serializer->serialize(dump($this->vehicleRepository->queryForExport()), 'sql-server-insert');
    }
}