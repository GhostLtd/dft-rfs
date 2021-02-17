<?php


namespace App\Utility\Domestic;


use App\Repository\Domestic\SurveyRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

class DataExporter
{
    /**
     * @var SurveyRepository
     */
    private $surveyRepository;

    /**
     * @var SqlServerInsertEncoder
     */
    private $sqlServerInsertEncoder;

    public function __construct(SurveyRepository $surveyRepository, SqlServerInsertEncoder $sqlServerInsertEncoder)
    {
        $this->surveyRepository = $surveyRepository;
        $this->sqlServerInsertEncoder = $sqlServerInsertEncoder;
    }

    public function getDataForExport(int $year, int $quarter) {
        $serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
        ], [
            $this->sqlServerInsertEncoder,
        ]);
        return $serializer->serialize($this->surveyRepository->queryForExport(), 'sql-server-insert');
    }
}