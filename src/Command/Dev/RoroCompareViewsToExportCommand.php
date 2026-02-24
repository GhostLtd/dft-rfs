<?php

namespace App\Command\Dev;

use App\Command\Dev\RoRo\RoRoTableComparator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'rfs:dev:roro-compare-views-to-export',
    description: 'Compare the RoRo export views to export files',
)]
class RoroCompareViewsToExportCommand extends AbstractInsertComparisonCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createIo($input, $output);

        $filename = $input->getArgument('filename');

        // CSRGT_export_2022_Q3_20250930_1312.sql
        if (!preg_match('/RORO_export_(?P<year>\d{4})_(?P<month>\d{1,2})_(.*?)_(.*?).sql/', $filename, $matches)) {
            $this->io->error('Unable to parse year and month from filename');
            return Command::FAILURE;
        }

        $year = intval($matches['year']);
        $month = intval($matches['month']);

        /** @var array<AbstractTableExportComparator> $comparators */
        $comparators = array_map(fn(string $x) => new $x($this->io, $this->entityManager), [
            RoRoTableComparator::class,
        ]);

        $data = $this->parseExportFile($filename, $comparators);
        if ($data === NULL) {
            return Command::FAILURE;
        }

        foreach($comparators as $comparator) {
            $table = $comparator->getExportTableName();
            $comparator->compare($this->columnNamesToIdx[$table], $data[$table], $year, $month);
        }

        return Command::SUCCESS;
    }
}
