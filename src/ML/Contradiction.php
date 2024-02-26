<?php

namespace App\ML;

class Contradiction
{
    /** @var array<string> */
    protected array $contradictions;

    public function __construct(protected mixed $sourceData)
    {
    }

    public function addContradiction(string $targetData): self
    {
        $this->contradictions[$targetData] ??= 0;
        $this->contradictions[$targetData] += 1;
        return $this;
    }

    public function getSourceData(): mixed
    {
        return $this->sourceData;
    }

    /** @return array<int,string> */
    public function getContradictions(): array
    {
        arsort($this->contradictions);
        return $this->contradictions;
    }

    public function getTotal(): int
    {
        return array_sum($this->contradictions);
    }
}