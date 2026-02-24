<?php

namespace App\Command\Dev;

use App\Command\Dev\DriverAvailability\DriverAvailabilityTableComparator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'rfs:dev:driver-availability-compare-views-to-export',
    description: 'Compare the Driver Availability export views to export files',
)]
class DriverAvailabilityCompareViewsToExportCommand extends AbstractCsvComparisonCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createIo($input, $output);

        $filename = $input->getArgument('filename');

        if (!preg_match('/driver-availability_export_(?P<year>\d{4})?_(?P<month>\d{1,2})?_.*?\.sql/', $filename, $matches)) {
            $this->io->error('Unable to parse filename');
            return Command::FAILURE;
        }

        $year = !empty($matches['year']) ? intval($matches['year']) : 0;
        $month = !empty($matches['month']) ? intval($matches['month']) : 0;

        /** @var array<AbstractTableExportComparator> $comparators */
        $comparators = [
            new DriverAvailabilityTableComparator($this->io, $this->entityManager),
        ];

        $data = $this->parseCsvExportFile($filename, $comparators);
        if ($data === null) {
            return Command::FAILURE;
        }

        foreach ($comparators as $comparator) {
            $table = $comparator->getExportTableName();
            $comparator->compare($this->columnNamesToIdx[$table], $data[$table], $year, $month);
        }

        return Command::SUCCESS;
    }

    protected function mapHeaderToColumnName(string $header): string
    {
        if (str_contains($header, ' - ')) {
            [$prefix, $suffix] = explode(' - ', $header, 2);
            $camelPrefix = $this->titleCaseToCamelCase($prefix);
            $pascalSuffix = join('', array_map('ucfirst', explode(' ', $suffix)));
            return "$camelPrefix -$pascalSuffix";
        }

        return $this->titleCaseToCamelCase($header);
    }

    private function titleCaseToCamelCase(string $titleCase): string
    {
        $parts = explode(' ', $titleCase);
        $parts[0] = lcfirst($parts[0]);
        for ($i = 1; $i < count($parts); $i++) {
            $parts[$i] = ucfirst($parts[$i]);
        }
        return join('', $parts);
    }
}
