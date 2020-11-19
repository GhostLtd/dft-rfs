<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait CargoTypeTrait
{
    /**
     * @ORM\Column(type="string", length=4)
     */
    private $cargoTypeCode;

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
