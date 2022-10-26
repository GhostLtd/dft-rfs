<?php

namespace App\ML;

class FileMapping
{
    public string $type;
    public array $mapping;

    public function __construct(string $type, array $mapping)
    {
        $this->type = $type;
        $this->mapping = $mapping;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }
}