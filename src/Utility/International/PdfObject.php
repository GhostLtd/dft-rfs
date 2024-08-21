<?php

namespace App\Utility\International;

use App\Entity\International\Survey;
use App\Utility\PdfObjectInterface;
use DateTime;
use Google\Cloud\Storage\StorageObject;

class PdfObject implements PdfObjectInterface
{
    public function __construct(protected Survey $survey, protected ?StorageObject $storageObject, protected string $firmReference, protected int $dispatchWeek, protected int $timestamp)
    {
    }

    public function getSurvey(): Survey
    {
        return $this->survey;
    }

    #[\Override]
    public function getStorageObject(): ?StorageObject
    {
        return $this->storageObject;
    }

    public function getFirmReference(): string
    {
        return $this->firmReference;
    }

    public function getDispatchWeek(): int
    {
        return $this->dispatchWeek;
    }

    #[\Override]
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    #[\Override]
    public function getComparator(): string
    {
        return $this->timestamp."-".$this->dispatchWeek."-".$this->getFirmReference();
    }

    public function getFilename($includeTimestamp = false): string
    {
        $timestamp = $includeTimestamp ? "_{$this->timestamp}" : '';
        return "{$this->firmReference}_{$this->dispatchWeek}{$timestamp}.pdf";
    }

    public function getDate(): DateTime
    {
        return DateTime::createFromFormat('U', $this->timestamp);
    }
}