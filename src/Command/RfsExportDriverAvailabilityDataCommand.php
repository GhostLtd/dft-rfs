<?php

namespace App\Command;

use App\Utility\Domestic\DriverAvailabilityExportHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RfsExportDriverAvailabilityDataCommand extends Command
{
    protected static $defaultName = 'rfs:export-driver-availability-data';
    protected static $defaultDescription = 'Export driver availability data as a CSV file';

    protected DriverAvailabilityExportHelper $exportHelper;

    public function __construct(DriverAvailabilityExportHelper $exportHelper)
    {
        $this->exportHelper = $exportHelper;
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
        $this->exportHelper->exportAll('php://output');
        return 0;
    }
}
