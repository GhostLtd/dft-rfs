<?php

namespace App\Entity\Domestic;

use App\Entity\VehicleTrait;
use App\Entity\Volume;
use App\Form\Validator as AppAssert;
use App\Repository\Domestic\VehicleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VehicleRepository::class)
 * @ORM\Table("domestic_vehicle")
 */
class Vehicle
{
    use VehicleTrait;

    /**
     * @ORM\Embedded(class=Volume::class)
     * @AppAssert\ValidValueUnit(allowBlank=true, groups={"vehicle_fuel_quantity"})
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
