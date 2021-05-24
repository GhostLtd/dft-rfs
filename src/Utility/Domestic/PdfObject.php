<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\Survey;
use App\Utility\PdfObjectInterface;
use DateTime;
use Google\Cloud\Storage\StorageObject;

class PdfObject implements PdfObjectInterface
{
    protected Survey $survey;
    protected ?StorageObject $storageObject;
    protected string $registrationMark;
    protected string $region;
    protected int $year;
    protected int $timestamp;

    public function __construct(Survey $survey, ?StorageObject $storageObject, string $registrationMark, string $region, int $year, int $timestamp)
    {
        $this->survey = $survey;
        $this->storageObject = $storageObject;
        $this->registrationMark = $registrationMark;
        $this->region = $region;
        $this->year = $year;
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

    public function getRegistrationMark(): string
    {
        return $this->registrationMark;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getComparator(): string
    {
        return $this->timestamp."-".$this->registrationMark."-".$this->getRegion();
    }

    public function getFilename($includeTimestamp = false): string
    {
        $timestamp = $includeTimestamp ? "_{$this->timestamp}" : '';
        return "{$this->registrationMark}_{$this->year}_{$this->region}{$timestamp}.pdf";
    }

    public function getDate(): DateTime
    {
        return DateTime::createFromFormat('U', $this->timestamp);
    }
}