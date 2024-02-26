<?php

namespace App\ML;

class DuplicateAndContradictionTracker
{
    /** @var array<string,Contradiction> */
    protected array $contradictions;
    protected array $duplicates;
    protected array $outputtedDuplicates;
    protected array $seen;

    public function __construct(protected Stats $stats)
    {
        $this->resetOutputtedDuplicates();

        $this->contradictions = [];
        $this->duplicates = [];
        $this->seen = [];
    }

    public function resetOutputtedDuplicates(): void {
        $this->outputtedDuplicates = [];
    }

    public function shouldOutput($sourceData, bool $recordStats): bool {
        $hash = $this->getHash($sourceData);

        $validityCode = $this->invalids[$hash] ?? false;
        if ($validityCode) {
            $recordStats && $this->stats->record($validityCode);
            return false;
        }

        if ($this->outputtedDuplicates[$hash] ?? false) {
            $recordStats && $this->stats->record(Stats::DUPLICATE);
            return false;
        }

        if ($this->duplicates[$hash] ?? false) {
            $this->outputtedDuplicates[$hash] = true;
            return true;
        }

        if ($this->contradictions[$hash] ?? false) {
            $recordStats && $this->stats->record(Stats::CONTRADICTORY);
            return false;
        }

        return true;
    }

    public function seen(mixed $sourceData, string $targetData): void {
        $hash = $this->getHash($sourceData);

        $this->seen[$hash] = ($this->seen[$hash] ?? 0) + 1;

        if ($this->seen[$hash] === 2) {
            $this->duplicates[$hash] = [$sourceData, $targetData];
        }

        if ($this->seen[$hash] > 2) {
            $existingContradiction = $this->contradictions[$hash] ?? false;

            if ($existingContradiction) {
                $this->contradictions[$hash]
                    ->addContradiction($targetData);
            } else {
                $previouslySeenTargetData = $this->duplicates[$hash][1];

                if ($previouslySeenTargetData !== $targetData) {
                    $this->contradictions[$hash] = (new Contradiction($sourceData))
                        ->addContradiction($previouslySeenTargetData)
                        ->addContradiction($targetData);

                    unset($this->duplicates[$hash]);
                }
            }
        }
    }

    public function getDuplicateRowsAndCounts(): \Generator {
        foreach($this->duplicates as $hash => [$sourceData, $targetData])
        {
            $count = $this->seen[$hash];
            yield [$sourceData, $targetData, $count];
        }
    }

    public function getContradictionsAndConfusions(): \Generator {
        uasort($this->contradictions, fn(Contradiction $a, Contradiction $b) =>
            $b->getTotal() <=> $a->getTotal()
        );

        foreach($this->contradictions as $contradiction) {
            yield $contradiction;
        }
    }

    protected function getHash(mixed $data): string
    {
        if (is_array($data)) {
            $data = join(',', $data);
        }

        return hash('sha256', $data);
    }
}