<?php

namespace App\Utility;

use Google\Cloud\Storage\StorageObject;

class ExportObject
{
    public function __construct(protected ?StorageObject $storageObject, protected int $year, protected int $quarter)
    {
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getQuarter(): int
    {
        return $this->quarter;
    }

    public function getStorageObject(): ?StorageObject
    {
        return $this->storageObject;
    }

    public function getComparator(): string
    {
        return $this->year."-".$this->quarter;
    }
}