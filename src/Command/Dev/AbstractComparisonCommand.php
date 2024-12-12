<?php

namespace App\Command\Dev;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractComparisonCommand extends Command
{
    protected array $columnNamesToIdx;
    protected array $columnIdxToNames;

    protected SymfonyStyle $io;

    public function __construct(protected EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'export.csv');
    }

    protected function createIo(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function getColumn(array $data, string $table, string $columnName): mixed
    {
        $key = $this->columnNamesToIdx[$table][$columnName] ?? null;

        if ($key === null) {
            debug_print_backtrace();
            $this->io->error("Unknown key in export data: {$table}.{$columnName}: {$key}");
            dump($this->columnNamesToIdx[$table]);
            exit;
        }

        if (!array_key_exists($key, $data)) {
            $this->io->error("Not found in export data: {$table}.{$columnName}");
            dump($data);
            exit;
        }

        return $data[$key];
    }

    protected final function parseValue(string $value): mixed
    {
        if ($value === 'NULL') {
            return null;
        }

        return $value;
    }

    /**
     * @param string $filename
     * @param array<AbstractTableExportComparator> $comparators
     * @return array|null
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

    protected function readFileLines(string $filename): \Generator
    {
        $handle = fopen($filename, "r");
        if ($handle === false) {
            throw new \RuntimeException("Could not open file: $filename");
        }

        try {
            while(($line = fgets($handle)) !== false) {
                yield rtrim($line, "\r\n");
            }
        } finally {
            fclose($handle);
        }
    }
}
