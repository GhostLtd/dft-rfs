<?php

namespace App\Command;

use App\Entity\Domestic\DriverAvailability;
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
                r.contactBusinessName,
                d.numberOfDriversEmployed,
                d.hasVacancies,
                d.numberOfDriverVacancies,
                d.reasonsForDriverVacancies,
                d.reasonsForDriverVacanciesOther,
                d.numberOfDriversThatHaveLeft,
                d.haveWagesIncreased,
                d.reasonsForWageIncrease,
                d.reasonsForWageIncreaseOther,
                d.hasPaidBonus,
                d.numberOfLorriesOperated,
                d.numberOfParkedLorries,
                d.hasMissedDeliveries,
                d.numberOfMissedDeliveries,
                d.averageWageIncrease.value,
                d.averageWageIncrease.unit,
                d.averageBonus.value,
                d.averageBonus.unit')
            ->where('r.contactBusinessName IS NOT NULL')
            ->getQuery()
            ->getArrayResult();

        $handle = fopen('php://output', 'w');

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
