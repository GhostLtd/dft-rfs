<?php

namespace App\Entity;

// This should be kept in sync with VehicleTrait
interface VehicleInterface
{
    public function getId(): ?string;
    public function getRegistrationMark(): ?string;
    public function setRegistrationMark(?string $registrationMark): self;
    public function getFormattedRegistrationMark(): ?string;
    public function getOperationType(): ?string;
    public function setOperationType(?string $operationType): self;
    public function getGrossWeight(): ?int;
    public function setGrossWeight(?int $grossWeight): self;
    public function getCarryingCapacity(): ?int;
    public function setCarryingCapacity(?int $carryingCapacity): self;
    public function getTrailerConfiguration(): ?int;
    public function setTrailerConfiguration(?int $trailerConfiguration): self;
    public function getAxleConfiguration(): ?int;
    public function setAxleConfiguration(?int $axleConfiguration): self;
    public function getBodyType(): ?string;
    public function setBodyType(?string $bodyType): self;
}
