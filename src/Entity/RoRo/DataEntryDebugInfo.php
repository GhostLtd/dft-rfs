<?php

namespace App\Entity\RoRo;

class DataEntryDebugInfo
{
    protected array $unparsableRows = [];
    protected array $unusedRows = [];

    protected ?int $totalPoweredVehicles = null;
    protected ?int $totalVehicles = null;

    protected bool $firstTimeSeen = true;

    public function addUnusedRow(string $countryName, string $countryCode, string $count): self {
        $this->unusedRows[] = [
            'countryName' => $countryName,
            // So that the translation in validators can {countryName, select ...} from this
            'countryCode' => ($countryCode === '') ? 'empty' : $countryCode,
            'count' => $count,
        ];
        return $this;
    }

    public function getUnusedRows(): array
    {
        return $this->unusedRows;
    }

    public function addUnparsableRow(string $row): self {
        $this->unparsableRows[] = $row;
        return $this;
    }

    public function getUnparsableRows(): array
    {
        return $this->unparsableRows;
    }

    public function getTotalPoweredVehicles(): ?int
    {
        return $this->totalPoweredVehicles;
    }

    public function setTotalPoweredVehicles(?int $totalPoweredVehicles): self
    {
        $this->totalPoweredVehicles = $totalPoweredVehicles;
        return $this;
    }

    public function getTotalVehicles(): ?int
    {
        return $this->totalVehicles;
    }

    public function setTotalVehicles(?int $totalVehicles): self
    {
        $this->totalVehicles = $totalVehicles;
        return $this;
    }

    // -----

    public function hasTotalVehicles(): bool
    {
        return $this->totalVehicles !== null;
    }

    public function isTotalVehiclesCorrect(int $actualCount): bool
    {
        return $this->totalVehicles === null || $this->totalVehicles === $actualCount;
    }

    public function hasTotalPoweredVehicles(): bool
    {
        return $this->totalPoweredVehicles !== null;
    }

    public function isTotalPoweredVehiclesCorrect(int $actualCount): bool
    {
        return $this->totalPoweredVehicles === null || $this->totalPoweredVehicles === $actualCount;
    }

    public function hasUnparsableRows(): bool
    {
        return !empty($this->unparsableRows);
    }

    public function hasUnusedRows(): bool
    {
        return !empty($this->unusedRows);
    }

    public function areAllCountsCorrect(array $counts): bool
    {
        return $this->isTotalVehiclesCorrect($counts['total']) &&
            $this->isTotalPoweredVehiclesCorrect($counts['total_powered']);
    }

    // A mechanism to allow the debug info to be flagged in the error-summary only on the first load of the
    // "edit vehicle count" pages (i.e. so that debug-related errors don't continue to be flagged after a user has
    // edited data)
    //
    // N.B. The debug info is still shown on the page. This only pertains to it being flagged in the error-summary
    public function isFirstTimeSeen(): bool
    {
        $value = $this->firstTimeSeen;
        $this->firstTimeSeen = false;
        return $value;
    }
}