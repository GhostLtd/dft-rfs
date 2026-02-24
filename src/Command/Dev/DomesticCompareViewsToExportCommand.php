<?php

namespace App\Command\Dev;

use App\Command\Dev\Domestic\StopDaysTableComparator;
use App\Command\Dev\Domestic\SummaryDaysTableComparator;
use App\Command\Dev\Domestic\VehicleTableComparator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'rfs:dev:csrgt-compare-views-to-export',
    description: 'Compare the CSRGT export views to export files',
)]
class DomesticCompareViewsToExportCommand extends AbstractInsertComparisonCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createIo($input, $output);

        $filename = $input->getArgument('filename');

        // CSRGT_export_2022_Q3_20250930_1312.sql
        if (!preg_match('/CSRGT_export_(?P<year>\d{4})_Q(?P<quarter>\d)_(.*?)_(.*?).sql/', $filename, $matches)) {
            $this->io->error('Unable to parse year and quarter from filename');
            return Command::FAILURE;
        }

        $year = intval($matches['year']);
        $quarter = intval($matches['quarter']);

        /** @var array<AbstractTableExportComparator> $comparators */
        $comparators = array_map(fn(string $x) => new $x($this->io, $this->entityManager), [
            StopDaysTableComparator::class,
            SummaryDaysTableComparator::class,
            VehicleTableComparator::class,
        ]);

        $data = $this->parseExportFile($filename, $comparators);
        if ($data === NULL) {
            return Command::FAILURE;
        }

        foreach($comparators as $comparator) {
            $table = $comparator->getExportTableName();
            $comparator->compare($this->columnNamesToIdx[$table], $data[$table], $year, $quarter);
        }

        return Command::SUCCESS;
    }
}
