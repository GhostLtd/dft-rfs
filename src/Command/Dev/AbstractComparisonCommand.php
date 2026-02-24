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
