<?php

namespace App\Command;

use App\Entity\Domestic\DriverAvailability;
use App\Entity\SurveyInterface;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RfsExportDriverAvailabilityDataCommand extends Command
{
    protected static $defaultName = 'rfs:export-driver-availability-data';
    protected static $defaultDescription = 'Export driver availability data as a CSV file';

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $responses = $this->entityManager
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

        $handle = fopen('php://output', 'w');

        $arrayColumns = [
            'reasonsForDriverVacancies' => DriverAvailability::VACANCY_REASON_CHOICES,
            'reasonsForWageIncrease' => DriverAvailability::WAGE_INCREASE_REASON_CHOICES,
        ];

        $responses = array_map(function($row) use ($arrayColumns) {
            $expandedRow = [];
            foreach($row as $key=>$value) {
                if ($arrayColumns[$key] ?? null) {
                    foreach($arrayColumns[$key] as $reason) {
                        if ($reason === 'other') {
                            $reason = 'isOther';
                        }

                        $camelCaseReason = join('', array_map('ucfirst', explode('-', $reason)));
                        $expandedRow[$key.' - '.$camelCaseReason] =
                            in_array($reason, $value ?? []) ? 1 : 0;
                    }
                } else {
                    if ($key === 'surveyPeriodStart') {
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

        $headers = array_map(function($name) {
            if ($name === 'contactBusinessName') {
                $name = 'businessName';
            }

            $parts = preg_split('/(?=[A-Z])/',$name);
            return ucfirst(join(' ', array_map("strtolower", $parts)));
        }, array_keys(current($responses)));
        fputcsv($handle, $headers);

        foreach($responses as $r) {
            $r['reasonsForDriverVacancies'] = join(', ', $r['reasonsForDriverVacancies'] ?? []);
            $r['reasonsForWageIncrease'] = join(', ', $r['reasonsForWageIncrease'] ?? []);
            fputcsv($handle, $r);
        }
        fclose($handle);

        return 0;
    }
}
