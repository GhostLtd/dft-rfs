<?php

namespace App\Utility\International;

use App\Entity\International\Survey;
use App\Entity\International\Trip;
use App\Repository\International\SurveyRepository;
use App\Repository\International\TripRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use App\Serializer\Normalizer\International\SurveyNormalizer;
use App\Serializer\Normalizer\International\TripActionsExportNormalizer;
use App\Serializer\Normalizer\International\TripNormalizer;
use App\Utility\Quarter\NaturalQuarterHelper;
use App\Utility\Export\AbstractDataExporter;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Throwable;

class DataExporter extends AbstractDataExporter
{
    protected Serializer $serializer;
    protected SurveyRepository $surveyRepository;
    protected TripRepository $tripRepository;

    public function __construct(
        SqlServerInsertEncoder                $sqlServerInsertEncoder,
        protected TripActionsExportNormalizer $tripActionsExportNormalizer,
        TripNormalizer                        $tripNormalizer,
        WorkflowInterface                     $internationalSurveyStateMachine,
        EntityManagerInterface                $entityManager,
        protected ExportHelper                $exportHelper,
        protected LoggerInterface             $logger,
        protected NaturalQuarterHelper        $naturalQuarterHelper,
        protected SerializerInterface         $symfonySerializer,
    )
    {
        parent::__construct($internationalSurveyStateMachine, $entityManager);

        $this->surveyRepository = $entityManager->getRepository(Survey::class);
        $this->tripRepository = $entityManager->getRepository(Trip::class);

        $this->serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s']),
            $tripNormalizer,
        ], [
            $sqlServerInsertEncoder
        ]);
    }

    public function generateExportData(int $year, int $quarter): ?string
    {
        [$startDate, $endDate] = $this->naturalQuarterHelper->getDateRangeForYearAndQuarter($year, $quarter);

        $now = new DateTime();

        try {
            $sqlFilename = tempnam(sys_get_temp_dir(), 'rfs-vehicle-irhs-');

            if (!$sqlFilename) {
                return null;
            }

            $surveys = $this->surveyRepository->getSurveysForExport($startDate, $endDate);
            $this->getFirmsSQL($surveys, $sqlFilename);
            file_put_contents($sqlFilename, "\n\n", FILE_APPEND);
            $this->entityManager->clear();

            $trips = $this->tripRepository->getTripsForExport($startDate, $endDate);
            $this->getTripsSQL($trips, $sqlFilename);
            file_put_contents($sqlFilename, "\n\n", FILE_APPEND);
            $this->getActionsSQL($trips, $sqlFilename);

            foreach ($trips as $trip) {
                $trip->setExportDate($now);
            }

            return $sqlFilename;
        } catch (Throwable $e) {
            $this->logger->error("[DataExporter] Export generation/upload failed", ['exception' => $e]);
            return null;
        }
    }

    /**
     * @param Survey[] | Collection $surveys
     */
    #[\Override]
    protected function confirmExport($surveys): array
    {
        $transitionName = 'confirm_export';
        $transitionIds = [];

        foreach ($surveys as $survey) {
            if ($this->isSurveyFullyExported($survey) && $this->workflow->can($survey, $transitionName)) {
                $this->workflow->apply($survey, $transitionName);
                $transitionIds[] = $survey->getId();
            }
        }
        $this->entityManager->flush();

        return $transitionIds;
    }

    protected function isSurveyFullyExported(Survey $survey): bool
    {
        foreach ($survey->getResponse()->getVehicles() as $vehicle) {
            foreach ($vehicle->getTrips() as $trip) {
                if ($trip->getExportDate() === null) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return Survey[]|array
     */
    public function getSurveysForTrips($trips): array
    {
        $surveys = [];
        foreach ($trips as $trip) {
            $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();
            $surveys[$survey->getId()] = $survey;
        }
        return $surveys;
    }

    protected function getFirmsSQL(array $firms, $outputFilename): void
    {
        file_put_contents(
            $outputFilename,
            $this->symfonySerializer->serialize($firms, 'sql-server-insert', [
                SurveyNormalizer::CONTEXT_KEY => true,
                SqlServerInsertEncoder::TABLE_NAME_KEY => "tblIHRFirmDetails",
                SqlServerInsertEncoder::FORCE_STRING_FIELDS => ['refNumber', 'contactPhone', 'businessSize',
                    'businessNature', 'reasonForEmptySurvey', 'activityStatus', 'contactEmail', 'contactName'],
            ]),
            FILE_APPEND
        );
    }

    protected function getTripsSQL(array $trips, $outputFilename): void
    {
        // This needs to be done in two parts, as the TripNormalizer doesn't normalize dates
        $tripNormData = $this->serializer->normalize($trips);
        file_put_contents(
            $outputFilename,
            $this->serializer->serialize($tripNormData, 'sql-server-insert', [
                SqlServerInsertEncoder::TABLE_NAME_KEY => "tblIHRVehicleDetails",
                SqlServerInsertEncoder::FORCE_STRING_FIELDS => ['VehicleOrigin', 'VehicleDestination'],
            ]),
            FILE_APPEND
        );
    }

    protected function getActionsSQL($surveys, $outputFilename): void
    {
        $normalized = $this->tripActionsExportNormalizer->normalize($surveys); // N.B. This isn't a Symfony normalizer
        file_put_contents(
            $outputFilename,
            $this->serializer->serialize($normalized, SqlServerInsertEncoder::FORMAT, [
                SqlServerInsertEncoder::TABLE_NAME_KEY => "tblIHRConsignmentDetails",
                SqlServerInsertEncoder::FORCE_STRING_FIELDS => ['DestinationPlace', 'OriginPlace'],
            ]),
            FILE_APPEND
        );
    }
}
