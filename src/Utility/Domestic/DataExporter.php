<?php


namespace App\Utility\Domestic;


use App\Repository\Domestic\DayStopRepository;
use App\Repository\Domestic\DaySummaryRepository;
use App\Repository\Domestic\SurveyRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use App\Serializer\Normalizer\Domestic\DetailedDayNormalizer;
use App\Serializer\Normalizer\Domestic\SummaryDayNormalizer;
use App\Serializer\Normalizer\Domestic\SurveyNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class DataExporter
{
    private SurveyRepository $surveyRepository;

    protected SerializerInterface $serializer;
    private SerializerInterface $surveySerializer;

    private SerializerInterface $summaryDaySerializer;
    private DaySummaryRepository $daySummaryRepository;
    private DayStopRepository $dayStopRepository;
    private SerializerInterface  $detailedDaySerializer;

    public function __construct(SurveyRepository $surveyRepository, DaySummaryRepository $daySummaryRepository, DayStopRepository $dayStopRepository, SurveyNormalizer $surveyNormalizer, SummaryDayNormalizer $summaryDayNormalizer, DetailedDayNormalizer $detailedDayNormalizer, SqlServerInsertEncoder $sqlServerInsertEncoder)
    {
        $this->surveyRepository = $surveyRepository;
        $this->daySummaryRepository = $daySummaryRepository;
        $this->dayStopRepository = $dayStopRepository;

        $this->serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
        ], [
            $sqlServerInsertEncoder
        ]);

        $this->surveySerializer = new Serializer([$surveyNormalizer], []);
        $this->summaryDaySerializer = new Serializer([$summaryDayNormalizer], []);
        $this->detailedDaySerializer = new Serializer([$detailedDayNormalizer], []);
    }

    public function getDataForExport(int $year, int $quarter) {
        return $this->getVehiclesSql($year, $quarter)
            . $this->getDetailedDaysSql($year, $quarter)
            . $this->getSummaryDaysSql($year, $quarter)
            ;
    }

    protected function getDetailedDaysSql(int $year, int $quarter)
    {
        $dayArrayData = $this->detailedDaySerializer->normalize($this->dayStopRepository->findAll());;
        return $this->serializer->serialize($dayArrayData, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "A_FourJourneys_{$year}Q{$quarter}"]);
    }

    protected function getSummaryDaysSql(int $year, int $quarter)
    {
        $dayArrayData = $this->summaryDaySerializer->normalize($this->daySummaryRepository->findAll());
        return $this->serializer->serialize($dayArrayData, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "A_FiveJourneys_{$year}Q{$quarter}"]);
    }

    protected function getVehiclesSql(int $year, int $quarter)
    {
        $surveyArrayData = $this->surveySerializer->normalize($this->surveyRepository->findByTypeWithResponseAndVehicle(true));
        return $this->serializer->serialize($surveyArrayData, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => "A_Vehicles_{$year}Q{$quarter}"]);
    }
}