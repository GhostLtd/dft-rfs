<?php

namespace App\Utility\Domestic;

use Google\Cloud\Storage\StorageObject;

class ExportObject
{
    protected int $year;
    protected int $quarter;
    protected ?StorageObject $storageObject;

    public function __construct(?StorageObject $storageObject, int $year, int $quarter)
    {
        $this->year = $year;
        $this->quarter = $quarter;
        $this->storageObject = $storageObject;
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