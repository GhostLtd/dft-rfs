<?php

namespace App\Command;

use App\ML\ConsoleLogger;
use App\ML\Config\DefaultTabularConfig;
use App\ML\InputAnalyser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// TODO: Combine with parent class (there is no longer needs to be a text/tabular command split)
#[AsCommand('rfs:ml:prepare-data-set', 'Process the input CSVs for a dataset')]
class RfsMLPrepareDataSetCommand extends AbstractMLDataSetCommand
{
    #[\Override]
    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument('input', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Input CSVs')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Dry run - do not write to output')
            ->addOption('show-contradictions', 'c', InputOption::VALUE_NONE, 'Show contradictions');
    }

    #[\Override]
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $showContradictions = $input->getOption('show-contradictions') ?? false;

        $consoleLogger = new ConsoleLogger($this->io, $this->logLevel);

        $config = new DefaultTabularConfig(); // TODO: Allow this to be chosen via options
//        $config = new DefaultTextConfig();

        $inputFiles = $input->getArgument('input');

        $analyser = (new InputAnalyser($config, $consoleLogger, $this->fileTypeMatcher))
            ->analyse($inputFiles);

        if (!$this->outputFile) {
            foreach ($analyser->getValidRecords() as $row) {
                // Dry run - do nothing...
            }
        } else {
            $outputHandle = fopen($this->outputFile, 'w');

            if ($config->shouldOutputHeader()) {
                fputcsv($outputHandle, $config->getCombinedColumnNames());
            }

            foreach ($analyser->getValidRecords() as $row) {
                fputcsv($outputHandle, $row);
            }
            fclose($outputHandle);
        }

        if ($showContradictions) {
            $duplicateAndContradictionTracker = $analyser->getDuplicateAndConfusionTracker();

            $rows = [];
            $addSep = false;
            foreach($duplicateAndContradictionTracker->getContradictionsAndConfusions() as $confusion) {
                $contractions = $confusion->getContradictions();
                $sourceData = array_values($confusion->getSourceData());
                $firstCell = (is_string($sourceData) && $sourceData !== '') ?
                    $sourceData :
                    json_encode($sourceData);

                if ($addSep) {
                    $rows[] = new TableSeparator();
                }

                $total = $confusion->getTotal();
                foreach($contractions as $target => $count) {
                    $desc = $this->nst2007->getDescription($target);
                    $percentage = number_format(100 * ($count / $total), 1).'%';
                    $rows[] = [$firstCell, $target, $count, $percentage, $desc];
                    $firstCell = '';
                }

                $addSep = true;
            }

            (new Table($output))
                ->setHeaders(['Source', 'Target', 'Count', '%', 'NST 2007 Description'])
                ->setRows($rows)
                ->setColumnMaxWidth(0, 64)
                ->setColumnMaxWidth(4, 94)
                ->render();
        }

        if ($this->showFrequencies) {
            $this->displayFrequencies($analyser);
        }

        return 0;
    }

    protected function displayFrequencies(InputAnalyser $analyser): void
    {
        $stats = $analyser->getStats();
        $this->io->table(['Outcome', 'Count'], $stats->asRows());

        $frequencyCounter = $analyser->getFrequencyCounter();

        $threshold = $frequencyCounter->getClassificationThreshold();

        $rows = [];
        $belowThreshold = new TableCellStyle([
            'fg' => 'bright-green',
        ]);

        foreach ($frequencyCounter->getFrequencies() as $code => $frequency) {
            $cellOptions = ($frequency < $threshold) ?
                ['style' => $belowThreshold] :
                [];

            $rows[] = [new TableCell($code, $cellOptions), $frequency];
        }

        uasort($rows, fn(array $a, array $b) => $b[1] <=> $a[1]);

        $this->io->table(['Code', 'Frequency'], $rows);
    }
}
