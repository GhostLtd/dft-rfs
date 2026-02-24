<?php

namespace App\Command\Dev;

abstract class AbstractCsvComparisonCommand extends AbstractComparisonCommand
{
    abstract protected function mapHeaderToColumnName(string $header): string;

    /**
     * @param array<AbstractTableExportComparator> $comparators
     */
    protected function parseCsvExportFile(string $filename, array $comparators): ?array
    {
        $comparator = $comparators[0];
        $table = $comparator->getExportTableName();

        $handle = fopen($filename, 'r');
        if ($handle === false) {
            $this->io->error("Could not open file: $filename");
            return null;
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                $this->io->error("Could not read CSV headers");
                return null;
            }

            $headerCount = count($headers);
            $internalHeaders = array_map([$this, 'mapHeaderToColumnName'], $headers);

            $this->columnNamesToIdx = [$table => array_flip($internalHeaders)];
            $this->columnIdxToNames = [$table => $internalHeaders];

            $data = [$table => []];
            while (($row = fgetcsv($handle)) !== false) {
                $row = array_slice($row, 0, $headerCount);

                $key = join('-', array_map(
                    fn(string $id) => $this->getColumn($row, $table, $id),
                    $comparator->getExportIdColumns()
                ));

                if (isset($data[$table][$key])) {
                    $this->io->error("Duplicate key: $key ($table)");
                    return null;
                }

                $data[$table][$key] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $data;
    }
}
