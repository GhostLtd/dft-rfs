<?php

namespace App\Entity\Utility;

use App\Entity\IdTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MaintenanceLock
{
    use IdTrait;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $whitelistedIps = [];

    public function getWhitelistedIps(): ?array
    {
        return $this->whitelistedIps;
    }

    public function setWhitelistedIps(?array $whitelistedIps): self
    {
        $this->whitelistedIps = $whitelistedIps;
        return $this;
    }
}
