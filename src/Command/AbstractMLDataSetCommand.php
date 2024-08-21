<?php

namespace App\Command;

use App\ML\ConsoleLogger;
use App\ML\FileTypeMatcher;
use App\ML\Nst2007;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractMLDataSetCommand extends Command
{
    abstract protected function doExecute(InputInterface $input, OutputInterface $output): int;

    protected ConsoleLogger $consoleLogger;
    protected SymfonyStyle $io;
    protected ?string $outputFile;
    protected int $logLevel;
    protected bool $showFrequencies;
    protected bool $showMemoryUsage;

    public function __construct(protected FileTypeMatcher $fileTypeMatcher, protected Nst2007 $nst2007)
    {
        $this->outputFile = null;
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('output', InputArgument::REQUIRED, 'Output CSV')

            ->addOption('log-level', 'l', InputOption::VALUE_OPTIONAL, 'Log level - bitmask - 1: error, 2: invalid text, 4: invalid code, 8: duplicate, 16: contradictory')
            ->addOption('show-frequencies', 'f', InputOption::VALUE_NONE, 'Show frequencies')
            ->addOption('show-memory-usage', 'u', InputOption::VALUE_NONE, 'Show memory usage');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $dryRun = $input->getOption('dry-run') ?? false;

        if (!$dryRun) {
            $this->outputFile = $input->getArgument('output');

            if (file_exists($this->outputFile)) {
                $this->io->error("Output file '{$this->outputFile}' already exists");
                return Command::FAILURE;
            }
        }

        $this->logLevel = intval($input->getOption('log-level') ?? ConsoleLogger::LEVEL_ERROR);
        $this->showFrequencies = $input->getOption('show-frequencies') ?? false;
        $this->showMemoryUsage = $input->getOption('show-memory-usage') ?? false;

        $this->consoleLogger = new ConsoleLogger($this->io, $this->logLevel);

        $result = $this->doExecute($input, $output);

        if ($this->showMemoryUsage) {
            $this->displayMemoryUsage();
        }
        return $result;
    }

    protected function displayMemoryUsage(): void
    {
        $readableBytes = function(int $size): string {
            $sizeNames = [" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB"];
            return $size ? round($size/1024 ** ($i = floor(log($size, 1024))), 2) .$sizeNames[$i] : '0 Bytes';
        };

        $usage = $readableBytes(memory_get_peak_usage());
        $this->io->success("Peak memory usage: {$usage}");
    }
}