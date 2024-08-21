<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait CargoTypeTrait
{
    #[Assert\NotNull(message: 'common.cargo-type.not-null', groups: ['cargo-type', 'admin_action_load', 'admin-day-stop-not-empty'])]
    #[ORM\Column(type: Types::STRING, length: 4, nullable: true)]
    private ?string $cargoTypeCode = null;

    public function getCargoTypeCode(): ?string
    {
        return $this->cargoTypeCode;
    }

    public function setCargoTypeCode(?string $cargoTypeCode): self
    {
        $this->cargoTypeCode = $cargoTypeCode;
        return $this;
    }
}
