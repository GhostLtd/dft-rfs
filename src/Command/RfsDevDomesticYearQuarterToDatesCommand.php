<?php

namespace App\Command;

use App\Utility\Quarter\CsrgtQuarterHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rfs:dev:domestic-year-quarter-to-dates',
    description: 'Displays date bounds for the given year/quarter',
)]
class RfsDevDomesticYearQuarterToDatesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('year', InputArgument::REQUIRED, 'Year')
            ->addArgument('quarter', InputArgument::REQUIRED, 'Quarter')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $year = $input->getArgument('year');
        $quarter = $input->getArgument('quarter');

        $helper = new CsrgtQuarterHelper();
        [$start, $end] = $helper->getDateRangeForYearAndQuarter($year, $quarter);

        $io->success("CSRGT {$year} Q{$quarter}: {$start->format('Y-m-d')} >= survey_period_start < {$end->format('Y-m-d')}");
        return Command::SUCCESS;
    }
}
