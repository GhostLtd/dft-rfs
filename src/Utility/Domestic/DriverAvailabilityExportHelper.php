<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\DriverAvailability;
use App\Entity\SurveyInterface;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DriverAvailabilityExportHelper
{
    protected EntityManagerInterface $entityManager;

    private const UNWANTED_STATES = [
        SurveyInterface::STATE_INVITATION_FAILED,
        SurveyInterface::STATE_INVITATION_PENDING,
        SurveyInterface::STATE_INVITATION_SENT,
        SurveyInterface::STATE_IN_PROGRESS,
        SurveyInterface::STATE_REJECTED,
    ];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function exportAll(string $exportPath = 'php://output')
    {
        $responses = $this->setParametersAndGetArrayResult($this->getQueryBuilder()->getQuery());
        $this->exportResponses($responses, $exportPath);
    }

    public function exportExisting(\DateTime $date, $exportPath = 'php://output')
    {
        $query = $this->getQueryBuilder()
            ->andWhere('d.exportedDate = :exportedDate')
            ->getQuery();

        $responses = $this->setParametersAndGetArrayResult($query, [
            'exportedDate' => $date
        ]);

        if (empty($responses)) {
            throw new BadRequestHttpException('no availability responses found for given date');
        }

        $this->exportResponses($responses, $exportPath);
    }

    public function exportNew(\DateTime $exportDate, $exportPath = 'php://output')
    {
        // get the ones with no exported date
        $query = $this->getQueryBuilder()
            ->andWhere('d.exportedDate IS NULL')
            ->getQuery();
        $responses = $this->setParametersAndGetArrayResult($query);

        // get all the IDs - set exported date
        $ids = array_map(fn($v) => $v['id'], $responses);
        $this->entityManager
            ->createQueryBuilder()
            ->update(DriverAvailability::class, 'd')
            ->set('d.exportedDate', ':exportedDate')
            ->where('d.id in (:ids)')
            ->getQuery()
            ->setParameters([
                'exportedDate' => $exportDate,
                'ids' => $ids,
            ])
            ->execute();

        // export
        $this->exportResponses($responses, $exportPath);
    }

    protected function exportResponses($responses, $exportPath)
    {
        $handle = fopen($exportPath, 'w');

        $arrayColumns = [
            'reasonsForDriverVacancies' => DriverAvailability::VACANCY_REASON_CHOICES,
            'reasonsForWageIncrease' => DriverAvailability::WAGE_INCREASE_REASON_CHOICES,
            'reasonsForBonuses' => DriverAvailability::BONUS_REASON_CHOICES,
        ];

        $responses = $this->expandColumns($arrayColumns, $responses);
        $headers = $this->getHeaders($responses);

        fputcsv($handle, $headers);

        foreach($responses as $r) {
            $r['reasonsForDriverVacancies'] = join(', ', $r['reasonsForDriverVacancies'] ?? []);
            $r['reasonsForWageIncrease'] = join(', ', $r['reasonsForWageIncrease'] ?? []);
            $r['reasonsForBonuses'] = join(', ', $r['reasonsForBonuses'] ?? []);

            $r['averageWageIncrease'] = $r['averageWageIncrease'] === null ? null : number_format($r['averageWageIncrease'] / 100, 2);
            $r['averageBonus'] = $r['averageBonus'] === null ? null : number_format($r['averageBonus'] / 100, 2);

            fputcsv($handle, $r);
        }
        fclose($handle);
    }

    protected function setParametersAndGetArrayResult(Query $query, $additionalParameters = [])
    {
        return $query->setParameters(array_merge($additionalParameters, [
            'unwantedStates' => self::UNWANTED_STATES
        ]))
            ->getArrayResult()
        ;
    }

    public function getExistingDates(): array
    {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->from(DriverAvailability::class, 'd')
            ->leftJoin('d.survey', 's')
            ->leftJoin('s.response', 'r')
            ->select('
                    d.exportedDate
                ')
            ->where('r.contactBusinessName IS NOT NULL')
            ->andWhere('d.exportedDate IS NOT NULL')
            ->andWhere('s.state NOT IN (:unwantedStates)')
            ->groupBy('d.exportedDate')
            ->orderBy('d.exportedDate', 'DESC')
            ->getQuery();
        $results = $this->setParametersAndGetArrayResult($query);
        return array_map(fn($v) => ($v['exportedDate'] ?? $v), $results);
    }

    public function hasAnyResponsesReadyForNewExport(): bool
    {
        // get the ones with no exported date
        $query = $this->getQueryBuilder()
            ->andWhere('d.exportedDate IS NULL')
            ->getQuery();
        $responses = $this->setParametersAndGetArrayResult($query);
        return !empty($responses);
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->entityManager
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
                d.numberOfMissedDeliveries')
            ->where('r.contactBusinessName IS NOT NULL')
            ->andWhere('s.state NOT IN (:unwantedStates)')
        ;
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