<?php

namespace App\Utility\International;

use DateTime;
use Google\Cloud\Storage\StorageObject;

class ExportObject
{
    protected int $week;
    protected ?StorageObject $storageObject;
    protected ?DateTime $startDate;
    protected ?DateTime $endDate;

    public function __construct(?StorageObject $storageObject, int $week, DateTime $startDate, DateTime $endDate)
    {
        $this->week = $week;
        $this->storageObject = $storageObject;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
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

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }
}