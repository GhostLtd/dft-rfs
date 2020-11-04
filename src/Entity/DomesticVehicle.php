<?php

namespace App\Entity;

use App\Repository\DomesticVehicleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DomesticVehicleRepository::class)
 */
class DomesticVehicle
{
    use VehicleTrait;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     */
    private $fuelQuantity;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $fuelUnit;

    public function getFuelQuantity(): ?string
    {
        return $this->fuelQuantity;
    }

    public function setFuelQuantity(string $fuelQuantity): self
    {
        $this->fuelQuantity = $fuelQuantity;

        return $this;
    }

    public function getFuelUnit(): ?string
    {
        return $this->fuelUnit;
    }

    public function setFuelUnit(string $fuelUnit): self
    {
        $this->fuelUnit = $fuelUnit;

        return $this;
    }
}
