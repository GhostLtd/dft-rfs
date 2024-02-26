<?php

namespace App\ML;

class FileMapping
{
    public function __construct(public string $type, public array $mapping)
    {
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