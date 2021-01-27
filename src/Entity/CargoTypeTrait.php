<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait CargoTypeTrait
{
    /**
     * @ORM\Column(type="string", length=4, nullable=true)
     * @Assert\NotNull(message="common.choice.not-null", groups={"cargo-type", "admin_action_load", "admin-day-stop-not-empty"})
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
