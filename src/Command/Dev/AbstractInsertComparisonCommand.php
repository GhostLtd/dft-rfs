<?php

namespace App\Command\Dev;

abstract class AbstractInsertComparisonCommand extends AbstractComparisonCommand
{
    protected final function parseValue(string $value): mixed
    {
        if ($value === 'NULL') {
            return null;
        }

        return $value;
    }

    /**
     * @param array<AbstractTableExportComparator> $comparators
     */
    protected function parseExportFile(string $filename, array $comparators): ?array
    {
        $table = 'Unknown';

        $comparator = null;
        $counts = [$table => 0];
        $data = [];

        $this->columnNamesToIdx = [];
        $this->columnIdxToNames = [];
        foreach($this->readFileLines($filename) as $line) {
            if (str_starts_with($line, 'INSERT')) {
                if (!preg_match('/^INSERT INTO \"(?:.*_)?(.*?)\"/', $line, $matches)) {
                    $this->io->error("Unable to parse table name: {$line}");
                    return null;
                }

                $table = $matches[1];
                $counts[$table] ??= 0;

                // Fetch the relevant comparator
                $comparator = null;
                foreach($comparators as $c) {
                    if ($c->getExportTableName() === $table) {
                        $comparator = $c;
                    }
                }

                if (!$comparator) {
                    $this->io->error("Unsupported table name: {$table}");
                    return null;
                }

                if (!preg_match('/\((.*)\)/', $line, $matches)) {
                    $this->io->error("Unable to parse column names: {$line}");
                    return null;
                }

                $this->columnIdxToNames[$table] ??= array_map(fn(string $x) => trim($x, "\r\n\t \""), explode(',', $matches[1]));
                $this->columnNamesToIdx[$table] ??= array_flip($this->columnIdxToNames[$table]);
                continue;
            }

            if (str_starts_with($line, '(')) {
                if (!$comparator) {
                    $this->io->error("Malformed input");
                    return null;
                }

                $parts = [];
                foreach($this->parseSqlTuple($line) as $idx => $part) {
                    $parts[$idx] = $this->parseValue($part);
                }

                $key = join('-', array_map(
                    fn(string $id) => $this->getColumn($parts, $table, $id),
                    $comparator->getExportIdColumns()
                ));

                $counts[$table]++;
                $data[$table] ??= [];

                if (isset($data[$table][$key])) {
                    $this->io->error("Duplicate key: $key ($table)");
                    return null;
                }

                $data[$table][$key] = $parts;
            }
        }

        return $data;
    }

    protected function parseSqlTuple(string $tuple): array
    {
        // Remove outer parentheses and any trailing commas/spaces
        $tuple = trim($tuple);
        $tuple = preg_replace('/^\(|\),?$/', '', $tuple);

        $result = [];
        $field = '';
        $inQuote = false;
        $len = strlen($tuple);

        for($i = 0; $i < $len; $i++) {
            $ch = $tuple[$i];

            if ($inQuote) {
                if ($ch === "'") {
                    // doubled single quote => literal '
                    if ($i + 1 < $len && $tuple[$i + 1] === "'") {
                        $field .= "'";
                        $i++;
                    } else {
                        $inQuote = false;
                    }
                } else {
                    $field .= $ch;
                }
            } else {
                if ($ch === "'") {
                    $inQuote = true;
                } elseif ($ch === ',') {
                    $result[] = trim($field);
                    $field = '';
                } else {
                    $field .= $ch;
                }
            }
        }

        if ($field !== '') {
            $result[] = trim($field);
        }

        return $result;
    }
}
