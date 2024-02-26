<?php

namespace App\ML;

use App\ML\Config\ConfigInterface;
use Generator;
use RuntimeException;

class InputAnalyser
{
    protected ?DuplicateAndContradictionTracker $duplicateAndConfusionTracker;
    protected array $filenames;
    protected ?FrequencyCounter $frequencyCounter;
    protected Stats $stats;

    public function __construct(protected ConfigInterface $config, protected ConsoleLogger $consoleLogger, protected FileTypeMatcher $fileTypeMatcher)
    {
        $this->duplicateAndConfusionTracker = null;
        $this->frequencyCounter = null;
        $this->stats = new Stats();
    }

    public function getStats(): Stats
    {
        return $this->stats;
    }

    public function getDuplicateAndConfusionTracker(): ?DuplicateAndContradictionTracker
    {
        return $this->duplicateAndConfusionTracker;
    }

    public function getFrequencyCounter(): ?FrequencyCounter
    {
        return $this->frequencyCounter;
    }

    public function analyse(array $filenames): self
    {
        $this->filenames = $filenames;
        $this->duplicateAndConfusionTracker = new DuplicateAndContradictionTracker($this->stats);
        $this->frequencyCounter = new FrequencyCounter();

        foreach ($this->dataPointIterator($filenames, true) as [$sourceData, $targetData, $row]) {
            $this->duplicateAndConfusionTracker->seen($sourceData, $targetData);
        }

        foreach ($this->dataPointIterator($filenames, false) as [$sourceData, $targetData]) {
            if ($this->duplicateAndConfusionTracker->shouldOutput($sourceData, false)) {
                $this->frequencyCounter->seen($targetData);
            }
        }

        $this->duplicateAndConfusionTracker->resetOutputtedDuplicates();

        return $this;
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function getValidRecords(bool $skipLowFrequency=false): Generator
    {
        if (!$this->duplicateAndConfusionTracker) {
            throw new RuntimeException('analyse() must be called before getValidRecords()');
        }

        foreach($this->dataPointIterator($this->filenames, false) as [$sourceData, $targetData, $row]) {
            if ($this->duplicateAndConfusionTracker->shouldOutput($sourceData, true)) {
                if ($this->frequencyCounter->isAboveThreshold($targetData)) {
                    yield $row;
                } else {
                    if ($skipLowFrequency) {
                        $this->stats->record(Stats::SKIPPED_LOW_FREQUENCY);
                    } else {
                        $this->stats->record(Stats::VALID_LOW_FREQUENCY);
                        $row[$this->config->getTargetColumnName()] = 'NO_MATCH';
                        yield $row;
                    }
                }
            }
        }
    }

    /**
     * @return Generator<array>
     */
    protected function dataPointIterator(array $filenames, bool $recordStats): Generator
    {
        $columnNames = $this->config->getCombinedColumnNames();
        $targetColumn = $this->config->getTargetColumnName();
        $rowTemplate = array_fill_keys($columnNames, null);

        foreach($filenames as $filename) {
            if (($handle = fopen($filename, "r")) === false) {
                $this->consoleLogger->log("Failed to open: '{$filename}'", ConsoleLogger::LEVEL_ERROR);
                return;
            }

            $fileMapping = false;
            while (($row = fgetcsv($handle, 4096, ",")) !== false) {
                if (!$fileMapping) {
                    // Use the header row to determine the file type + mapping
                    $fileMapping = $this->fileTypeMatcher->getFileMapping($row, $this->config);

                    if (!$fileMapping) {
                        throw new RuntimeException("Unknown file type for file: '{$filename}");
                    }

                    continue;
                }

                $filteredRow = $rowTemplate;
                foreach($fileMapping->getMapping() as ['header' => $header, 'key' => $key]) {
                    $text = trim($row[$key]);
                    if (mb_check_encoding($text, 'ISO-8859-1')) {
                        $convertedText = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');

                        if ($convertedText === false) {
                            // Couldn't convert to UTF-8
                            $recordStats && $this->stats->record(Stats::INVALID_TEXT);
                            $this->consoleLogger->log("Invalid text: {$text}", ConsoleLogger::LEVEL_INVALID_TEXT);
                            continue;
                        }

                        $text = $convertedText;
                    } else {
                        // Input text doesn't look like expected ISO-8859-1
                        $recordStats && $this->stats->record(Stats::INVALID_TEXT);
                        $this->consoleLogger->log("Invalid text: {$text}", ConsoleLogger::LEVEL_INVALID_TEXT);
                        continue;
                    }

                    if ($text === 'NULL') {
                        $text = '';
                    }

                    $filteredRow[$header] = $text;
                }

                try {
                    $filteredRow = $this->config->normalizeAndErrorCheck($filteredRow);

                    $target = $filteredRow[$targetColumn];

                    $rowWithoutTarget = $filteredRow;
                    unset($rowWithoutTarget[$targetColumn]);

                    $recordStats && $this->stats->record(Stats::VALID);
                    yield [$rowWithoutTarget, $target, $filteredRow];
                }
                catch(ValidationException $e) {
                    $recordStats && $this->stats->record($e->getMessage());
                }
            }

            fclose($handle);
        }
    }
}