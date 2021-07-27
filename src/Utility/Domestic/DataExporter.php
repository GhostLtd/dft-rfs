<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Repository\Domestic\DayStopRepository;
use App\Repository\Domestic\DaySummaryRepository;
use App\Repository\Domestic\SurveyRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use App\Serializer\Normalizer\Domestic\DetailedDayNormalizer;
use App\Serializer\Normalizer\Domestic\SummaryDayNormalizer;
use App\Serializer\Normalizer\Domestic\SurveyNormalizer;
use App\Utility\Export\AbstractDataExporter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class DataExporter extends AbstractDataExporter
{
    private SurveyRepository $surveyRepository;

    protected SerializerInterface $serializer;
    protected SerializerInterface $surveySerializer;

    protected SerializerInterface $summaryDaySerializer;
    protected DaySummaryRepository $daySummaryRepository;
    protected DayStopRepository $dayStopRepository;
    protected SerializerInterface  $detailedDaySerializer;
    protected ExportHelper $exportHelper;
    protected LoggerInterface $logger;

    public function __construct(ExportHelper $exportHelper, WorkflowInterface $domesticSurveyStateMachine, EntityManagerInterface $entityManager, SurveyNormalizer $surveyNormalizer, SummaryDayNormalizer $summaryDayNormalizer, DetailedDayNormalizer $detailedDayNormalizer, SqlServerInsertEncoder $sqlServerInsertEncoder, LoggerInterface $logger)
    {
        parent::__construct($domesticSurveyStateMachine, $entityManager);

        $this->surveyRepository = $entityManager->getRepository(Survey::class);
        $this->daySummaryRepository = $entityManager->getRepository(DaySummary::class);
        $this->dayStopRepository = $entityManager->getRepository(DayStop::class);

        $this->serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
        ], [
            $sqlServerInsertEncoder
        ]);

        $this->surveySerializer = new Serializer([$surveyNormalizer], []);
        $this->summaryDaySerializer = new Serializer([$summaryDayNormalizer], []);
        $this->detailedDaySerializer = new Serializer([$detailedDayNormalizer], []);
        $this->exportHelper = $exportHelper;
        $this->logger = $logger;
    }

    public function uploadExportData(int $year, int $quarter): bool {
        $surveys = $this->surveyRepository->findForExport($year, $quarter);
        try {
            $this->startExport($surveys);
            $sql = $this->getVehiclesSql($surveys, "rf{$year}.dbo.q{$quarter}_a_raw_CSRGT_vehicles")."\n"
                . $this->getDetailedDaysSql($surveys, "rf{$year}.dbo.q{$quarter}_a_raw_CSRGT_fourjourneys")."\n"
                . $this->getSummaryDaysSql($surveys, "rf{$year}.dbo.q{$quarter}_a_raw_CSRGT_fivejourneys")."\n"
                ;
            $this->exportHelper->upload($sql, $year, $quarter);
            $this->confirmExport($surveys);
            return true;
        } catch (\Throwable $e) {
            $this->cancelExport($surveys);
            $this->logger->error("[DataExporter] Export generation/upload failed", ['exception' => $e]);
            return false;
        }
    }

    protected function getDetailedDaysSql($surveys, $filename)
    {
        $dayArrayData = $this->detailedDaySerializer->normalize($this->dayStopRepository->findForExport($surveys));
        return $this->serializer->serialize($dayArrayData, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => $filename]);
    }

    protected function getSummaryDaysSql($surveys, $filename)
    {
        $dayArrayData = $this->summaryDaySerializer->normalize($this->daySummaryRepository->findForExport($surveys));
        return $this->serializer->serialize($dayArrayData, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => $filename]);
    }

    protected function getVehiclesSql($surveys, $filename)
    {
        $surveyArrayData = $this->surveySerializer->normalize($surveys);
        return $this->serializer->serialize($surveyArrayData, 'sql-server-insert', [SqlServerInsertEncoder::TABLE_NAME_KEY => $filename]);
    }
}