<?php

namespace App\Utility;

use App\Entity\Domestic\DriverAvailability;
use App\Entity\SurveyInterface;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\ORM\EntityManagerInterface;

class DriverAvailabilityExportHelper
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function export(string $exportPath)
    {
        $responses = $this->getResponses();

        $handle = fopen($exportPath, 'w');

        $arrayColumns = [
            'reasonsForDriverVacancies' => DriverAvailability::VACANCY_REASON_CHOICES,
            'reasonsForWageIncrease' => DriverAvailability::WAGE_INCREASE_REASON_CHOICES,
        ];

        $responses = $this->expandColumns($arrayColumns, $responses);
        $headers = $this->getHeaders($responses);

        fputcsv($handle, $headers);

        foreach($responses as $r) {
            $r['reasonsForDriverVacancies'] = join(', ', $r['reasonsForDriverVacancies'] ?? []);
            $r['reasonsForWageIncrease'] = join(', ', $r['reasonsForWageIncrease'] ?? []);
            fputcsv($handle, $r);
        }
        fclose($handle);
    }

    public function getResponses(): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->from(DriverAvailability::class, 'd')
            ->leftJoin('d.survey', 's')
            ->leftJoin('s.response', 'r')
            ->select('
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
                d.averageWageIncrease.value,
                d.averageWageIncrease.unit,
                d.reasonsForWageIncrease,
                d.reasonsForWageIncreaseOther,
                
                d.hasPaidBonus,
                d.averageBonus.value,
                d.averageBonus.unit,
                d.numberOfParkedLorries,
                d.hasMissedDeliveries,
                d.numberOfMissedDeliveries')
            ->where('r.contactBusinessName IS NOT NULL')
            ->andWhere('s.state NOT IN (:unwantedStates)')
            ->getQuery()
            ->setParameters([
                'unwantedStates' => [
                    SurveyInterface::STATE_INVITATION_FAILED,
                    SurveyInterface::STATE_INVITATION_PENDING,
                    SurveyInterface::STATE_INVITATION_SENT,
                    SurveyInterface::STATE_IN_PROGRESS,
                    SurveyInterface::STATE_REJECTED,
                ],
            ])
            ->getArrayResult();
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
                    if ($key === 'surveyPeriodStart') {
                        // Expand surveyPeriodStart into survey year/week number
                        [$week, $year] = WeekNumberHelper::getWeekNumberAndYear($value);
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
            $expandedRow[$columnName . ' - ' . $camelCaseReason] =
                in_array($choice, $value ?? []) ? 1 : 0;
        }
    }
}