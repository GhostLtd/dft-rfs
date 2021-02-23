<?php

namespace App\Utility\Google;

use Google\Cloud\Storage\StorageObject;

class InternationalExportObject
{
    protected int $week;
    protected ?StorageObject $storageObject;

    public function __construct(?StorageObject $storageObject, int $week)
    {
        $this->week = $week;
        $this->storageObject = $storageObject;
    }

    public function getWeek(): int
    {
        return $this->week;
    }

    public function getStorageObject(): ?StorageObject
    {
        return $this->storageObject;
    }

    public function getComparator(): string
    {
        return 'W'.$this->week;
    }
}