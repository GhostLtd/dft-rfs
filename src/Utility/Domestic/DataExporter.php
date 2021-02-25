<?php


namespace App\Utility\Domestic;


use App\Repository\Domestic\SurveyRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use App\Serializer\Normalizer\Domestic\SurveyNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class DataExporter
{
    /**
     * @var SurveyRepository
     */
    private $surveyRepository;

    protected SerializerInterface $serializer;

    public function __construct(SurveyRepository $surveyRepository, SurveyNormalizer $surveyNormalizer, SqlServerInsertEncoder $sqlServerInsertEncoder)
    {
        $this->surveyRepository = $surveyRepository;
        $this->surveySerializer = new Serializer([$surveyNormalizer], []);
        $this->serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
        ], [
            $sqlServerInsertEncoder
        ]);
    }

    public function getDataForExport(int $year, int $quarter) {
        return $this->getVehiclesSql($year, $quarter)
            . $this->getDetailedDaysSql($year, $quarter)
            . $this->getSummaryDaysSql($year, $quarter)
            ;
    }

    protected function getDetailedDaysSql(int $year, int $quarter)
    {
        $dayArrayData = [];
        return $this->serializer->serialize($dayArrayData, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "A_FourJourneys_{$year}Q{$quarter}"]);
    }

    protected function getSummaryDaysSql(int $year, int $quarter)
    {
        $dayArrayData = [
            [
                'test' => 'wobble',
            ]
        ];
        return $this->serializer->serialize($dayArrayData, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "A_FiveJourneys_{$year}Q{$quarter}"]);
    }

    protected function getVehiclesSql(int $year, int $quarter)
    {
        $surveyArrayData = $this->surveySerializer->normalize($this->surveyRepository->findByTypeWithResponseAndVehicle(true));
        return $this->serializer->serialize($surveyArrayData, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "A_Vehicles_{$year}Q{$quarter}"]);
    }
}