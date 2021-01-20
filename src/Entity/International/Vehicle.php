<?php

namespace App\Entity\International;

use App\Entity\BlameLoggable;
use App\Entity\VehicleTrait;
use App\Form\Validator as AppAssert;
use App\Repository\International\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VehicleRepository::class)
 * @ORM\Table(name="international_vehicle")
 *
 * @AppAssert\ValidRegistration(groups={"vehicle_registration", "admin_vehicle"})
 */
class Vehicle implements BlameLoggable
{
    use VehicleTrait;

    /**
     * @ORM\ManyToOne(targetEntity=SurveyResponse::class, inversedBy="vehicles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $surveyResponse;

    /**
     * @ORM\OneToMany(targetEntity=Trip::class, mappedBy="vehicle")
     * @ORM\OrderBy({"outboundDate": "ASC", "returnDate": "ASC"})
     */
    private $trips;

    public function __construct()
    {
        $this->trips = new ArrayCollection();
    }

    public function getSurveyResponse(): ?SurveyResponse
    {
        return $this->surveyResponse;
    }

    public function setSurveyResponse(?SurveyResponse $surveyResponse): self
    {
        $this->surveyResponse = $surveyResponse;

        return $this;
    }

    /**
     * @return Collection|Trip[]
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trip $trip): self
    {
        if (!$this->trips->contains($trip)) {
            $this->trips[] = $trip;
            $trip->setVehicle($this);
        }

        return $this;
    }

    public function removeTrip(Trip $trip): self
    {
        if ($this->trips->removeElement($trip)) {
            // set the owning side to null (unless already changed)
            if ($trip->getVehicle() === $this) {
                $trip->setVehicle(null);
            }
        }

        return $this;
    }

    public function mergeVehicleChanges(Vehicle $vehicle)
    {
        $this->setRegistrationMark($vehicle->getRegistrationMark());
        $this->setOperationType($vehicle->getOperationType());
        $this->setAxleConfiguration($vehicle->getAxleConfiguration());
        $this->setTrailerConfiguration($vehicle->getTrailerConfiguration()); // Order is important
        $this->setBodyType($vehicle->getBodyType());
        $this->setCarryingCapacity($vehicle->getCarryingCapacity());
        $this->setGrossWeight($vehicle->getGrossWeight());
    }

    public function getBlameLogLabel()
    {
        return "{$this->getRegistrationMark()}";
    }

    public function getAssociatedEntityClass()
    {
        return SurveyResponse::class;
    }

    public function getAssociatedEntityId()
    {
        return $this->getSurveyResponse()->getId();
    }
}
