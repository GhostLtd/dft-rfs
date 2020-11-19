<?php

namespace App\Entity\International;

use App\Entity\VehicleTrait;
use App\Repository\International\VehicleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VehicleRepository::class)
 * @ORM\Table(name="international_vehicle")
 */
class Vehicle
{
    use VehicleTrait;

    /**
     * @ORM\ManyToOne(targetEntity=SurveyResponse::class, inversedBy="vehicles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $surveyResponse;

    /**
     * @ORM\OneToOne(targetEntity=Trip::class, mappedBy="vehicle", cascade={"persist", "remove"})
     */
    private $trip;

    public function getSurveyResponse(): ?SurveyResponse
    {
        return $this->surveyResponse;
    }

    public function setSurveyResponse(?SurveyResponse $surveyResponse): self
    {
        $this->surveyResponse = $surveyResponse;

        return $this;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(Trip $trip): self
    {
        $this->trip = $trip;

        // set the owning side of the relation if necessary
        if ($trip->getVehicle() !== $this) {
            $trip->setVehicle($this);
        }

        return $this;
    }
}
