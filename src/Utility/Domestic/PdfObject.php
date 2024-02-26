<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\Survey;
use App\Utility\PdfObjectInterface;
use DateTime;
use Google\Cloud\Storage\StorageObject;

class PdfObject implements PdfObjectInterface
{
    public function __construct(protected Survey $survey, protected ?StorageObject $storageObject, protected string $registrationMark, protected string $region, protected int $year, private bool $isReissue, protected int $timestamp)
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

    #[\Override]
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    #[\Override]
    public function getComparator(): string
    {
        return $this->timestamp."-".$this->registrationMark."-".$this->getRegion();
    }

    public function getFilename($includeTimestamp = false): string
    {
        $timestamp = $includeTimestamp ? "_{$this->timestamp}" : '';
        return "{$this->registrationMark}_{$this->year}" . ($this->isReissue ? '_R' : '') . "_{$this->region}{$timestamp}.pdf";
    }

    public function getDate(): DateTime
    {
        return DateTime::createFromFormat('U', $this->timestamp);
    }

    public function isReissue(): bool
    {
        return $this->isReissue;
    }
}