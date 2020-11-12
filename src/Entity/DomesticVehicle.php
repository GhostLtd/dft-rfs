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
     * @ORM\Embedded(class=Volume::class)
     */
    private $fuelQuantity;

    public function getFuelQuantity(): ?Volume
    {
        return $this->fuelQuantity;
    }

    public function setFuelQuantity(Volume $fuelQuantity): self
    {
        $this->fuelQuantity = $fuelQuantity;

        return $this;
    }
}
