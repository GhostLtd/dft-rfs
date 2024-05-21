<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\DriverAvailability;
use App\Entity\SurveyStateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class DriverAvailabilityDataExporter
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface        $logger,
    ) {}

    public function generateExportDataForYearAndMonth(int $year, int $month, bool $outputToStdout = false): ?string
    {
        $minDate = \DateTime::createFromFormat('Y-m-d H:i:s', "$year-$month-01 00:00:00");
        $maxDate = (clone $minDate)->modify('+1 month');

        $responses = $this->getSurveysForExport($minDate, $maxDate);
        return $this->exportResponses($responses, $outputToStdout);
    }

    public function generateAllExportData(bool $outputToStdout = false): ?string
    {
        $responses = $this->getSurveysForExport();
        return $this->exportResponses($responses, $outputToStdout);
    }

    protected function exportResponses(array $responses, bool $outputToStdout = false): ?string
    {
        try {
            if ($outputToStdout) {
                $exportPath = 'php://stdout';
            } else {
                $exportPath = tempnam(sys_get_temp_dir(), 'rfs-data-availability');
            }

            $handle = fopen($exportPath, 'w');

            $arrayColumns = [
                'reasonsForDriverVacancies' => DriverAvailability::VACANCY_REASON_CHOICES,
                'reasonsForWageIncrease' => DriverAvailability::WAGE_INCREASE_REASON_CHOICES,
                'reasonsForBonuses' => DriverAvailability::BONUS_REASON_CHOICES,
            ];

            $responses = $this->expandColumns($arrayColumns, $responses);
            $headers = $this->getHeaders($responses);

            fputcsv($handle, $headers);

            foreach ($responses as $r) {
                $r['reasonsForDriverVacancies'] = join(', ', $r['reasonsForDriverVacancies'] ?? []);
                $r['reasonsForWageIncrease'] = join(', ', $r['reasonsForWageIncrease'] ?? []);
                $r['reasonsForBonuses'] = join(', ', $r['reasonsForBonuses'] ?? []);

                $r['averageWageIncrease'] = $r['averageWageIncrease'] === null ? null : number_format($r['averageWageIncrease'] / 100, 2);
                $r['averageBonus'] = $r['averageBonus'] === null ? null : number_format($r['averageBonus'] / 100, 2);

                fputcsv($handle, $r);
            }
            fclose($handle);

            return $exportPath;
        } catch (Throwable $e) {
            $this->logger->error("[DataExporter] Export generation/upload failed", ['exception' => $e]);
            return null;
        }
    }

    protected function getSurveysForExport(?\DateTime $minDate = null, ?\DateTime $maxDate = null): array
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->from(DriverAvailability::class, 'd')
            ->leftJoin('d.survey', 's')
            ->leftJoin('s.response', 'r')
            ->select('
                d.id,
                s.registrationMark,
                s.state,
                s.submissionDate,
                s.surveyPeriodStart,
                
                s.isNorthernIreland,
                
                r.contactBusinessName,
                r.businessNature,
                r.numberOfEmployees,
                d.numberOfDriversEmployed,
                d.numberOfLorriesOperated,
                                
                d.hasVacancies,
                d.numberOfDriverVacancies,               
                d.reasonsForDriverVacancies,
                d.reasonsForDriverVacanciesOther,
                
                d.numberOfDriversThatHaveLeft,
                d.haveWagesIncreased,
                d.averageWageIncrease,
                d.wageIncreasePeriod,
                d.wageIncreasePeriodOther,
                d.reasonsForWageIncrease,
                d.reasonsForWageIncreaseOther,
                
                d.hasPaidBonus,
                d.averageBonus,
                d.reasonsForBonuses,
                d.numberOfParkedLorries,
                d.hasMissedDeliveries,
                d.numberOfMissedDeliveries'
            )
            ->where('r.contactBusinessName IS NOT NULL')
            ->andWhere('s.state NOT IN (:unwantedStates)');

        if ($minDate !== null) {
            $qb
                ->andWhere('s.submissionDate >= :minDate')
                ->setParameter('minDate', $minDate);
        }

        if ($maxDate !== null) {
            $qb
                ->andWhere('s.submissionDate < :maxDate')
                ->setParameter('maxDate', $maxDate);
        }

        return $qb
            ->setParameter('unwantedStates', [
                SurveyStateInterface::STATE_INVITATION_FAILED,
                SurveyStateInterface::STATE_INVITATION_PENDING,
                SurveyStateInterface::STATE_INVITATION_SENT,
                SurveyStateInterface::STATE_IN_PROGRESS,
                SurveyStateInterface::STATE_REJECTED,
            ])
            ->orderBy('s.submissionDate', 'ASC')
            ->getQuery()
            ->execute();
    }

    public function expandColumns(array $arrayColumns, $responses): array
    {
        return array_map(function ($row) use ($arrayColumns) {
            $expandedRow = [];
            foreach ($row as $key => $value) {
                if ($arrayColumns[$key] ?? null) {
                    // Expand a JSON array column into individual bitwise columns
                    $this->expandArrayColumn($arrayColumns[$key], $key, $value, $expandedRow);
                } else {
                    if ($key === 'id') {
                        // ID is needed for some scenarios, but we want to exclude it from the export.
                        continue;
                    } else if ($key === 'surveyPeriodStart') {
                        // Expand surveyPeriodStart into survey year/week number
                        [$week, $year] = WeekNumberHelper::getYearlyWeekNumberAndYear($value);
                        $expandedRow['yearNumber'] = $year;
                        $expandedRow['weekNumber'] = $week;
                        continue;
                    } else if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    $expandedRow[$key] = $value;
                }
            }

            return $expandedRow;
        }, $responses);
    }

    public function getHeaders(array $responses): array
    {
        if (empty($responses)) {
            return [];
        }

        return array_map(function ($name) {
            if ($name === 'contactBusinessName') {
                $name = 'businessName';
            }

            $parts = preg_split('/(?=[A-Z])/', $name);
            return ucfirst(join(' ', array_map("strtolower", $parts)));
        }, array_keys(current($responses)));
    }

    public function expandArrayColumn($choices, $columnName, $value, array &$expandedRow): void
    {
        // e.g.
        //      choices: [apple, banana, orange, pineapple]
        //      columnName: fruit
        //      value: [apple, orange]
        //
        // becomes columns:
        //      fruit_apple: 1,
        //      fruit_banana: 0,
        //      fruit_orange: 1,
        //      fruit_pineapple: 0,
        foreach ($choices as $choice) {
            if ($choice === 'other') {
                $choice = 'isOther';
            }

            $camelCaseReason = join('', array_map('ucfirst', explode('-', $choice)));
            $expandedRow[$columnName . ' -' . $camelCaseReason] =
                in_array($choice, $value ?? []) ? 1 : 0;
        }
    }
}
