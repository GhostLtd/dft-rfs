<?php

namespace App\Entity;

// This should be kept in sync with CargoTypeTrait
interface CargoTypeInterface
{
    public function getCargoTypeCode(): ?string;
    public function setCargoTypeCode(?string $cargoTypeCode): self;
}