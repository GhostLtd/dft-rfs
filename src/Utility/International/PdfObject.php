<?php

namespace App\Utility\International;

use App\Entity\International\Survey;
use App\Utility\PdfObjectInterface;
use DateTime;
use Google\Cloud\Storage\StorageObject;

class PdfObject implements PdfObjectInterface
{
    protected Survey $survey;
    protected ?StorageObject $storageObject;
    protected string $firmReference;
    protected int $dispatchWeek;
    protected int $timestamp;

    public function __construct(Survey $survey, ?StorageObject $storageObject, string $firmReference, int $dispatchWeek, int $timestamp)
    {
        $this->survey = $survey;
        $this->storageObject = $storageObject;
        $this->firmReference = $firmReference;
        $this->dispatchWeek = $dispatchWeek;
        $this->timestamp = $timestamp;
    }

    public function getSurvey(): Survey
    {
        return $this->survey;
    }

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

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

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