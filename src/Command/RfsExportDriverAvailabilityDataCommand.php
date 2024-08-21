<?php

namespace App\Command;

use App\Utility\Domestic\DriverAvailabilityDataExporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('rfs:export-driver-availability-data', 'Export driver availability data as a CSV file')]
class RfsExportDriverAvailabilityDataCommand extends Command
{
    public function __construct(protected DriverAvailabilityDataExporter $dataExporter)
    {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dataExporter->generateAllExportData(true);
        return 0;
    }
}
