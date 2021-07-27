<?php

namespace App\Entity\Domestic;

use App\Entity\VehicleTrait;
use App\Entity\Volume;
use App\Form\Validator as AppAssert;
use App\Repository\Domestic\VehicleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert; // N.B. Used by trait

/**
 * @ORM\Entity(repositoryClass=VehicleRepository::class)
 * @ORM\Table("domestic_vehicle")
 */
class Vehicle
{
    use VehicleTrait;

    /**
     * @ORM\Embedded(class=Volume::class)
     * @AppAssert\ValidValueUnit(allowBlank=true, groups={"vehicle_fuel_quantity", "admin_vehicle_fuel_quantity"})
     */
    private $fuelQuantity;

    /**
     * @var SurveyResponse
     * @ORM\OneToOne(targetEntity=SurveyResponse::class, mappedBy="vehicle", cascade={"persist"})
     */
    private $response;

    public function getFuelQuantity(): ?Volume
    {
        return $this->fuelQuantity;
    }

    public function setFuelQuantity(Volume $fuelQuantity): self
    {
        $this->fuelQuantity = $fuelQuantity;

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(?SurveyResponse $response): self
    {
        $this->response = $response;

        // set the owning side of the relation if necessary
        if ($response->getVehicle() !== $this) {
            $response->setVehicle($this);
        }

        return $this;
    }
}
