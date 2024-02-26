<?php

namespace App\Command;

use App\Utility\Domestic\DriverAvailabilityExportHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('rfs:export-driver-availability-data', 'Export driver availability data as a CSV file')]
class RfsExportDriverAvailabilityDataCommand extends Command
{
    public function __construct(protected DriverAvailabilityExportHelper $exportHelper)
    {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->exportHelper->exportAll('php://output');
        return 0;
    }
}
