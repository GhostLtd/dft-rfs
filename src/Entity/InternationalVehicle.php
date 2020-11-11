<?php

namespace App\Entity;

use App\Repository\InternationalVehicleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternationalVehicleRepository::class)
 */
class InternationalVehicle
{
    use VehicleTrait;

    /**
     * @ORM\ManyToOne(targetEntity=InternationalSurveyResponse::class, inversedBy="vehicles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $surveyResponse;

    /**
     * @ORM\OneToOne(targetEntity=InternationalTrip::class, mappedBy="vehicle", cascade={"persist", "remove"})
     */
    private $trip;

    public function getSurveyResponse(): ?InternationalSurveyResponse
    {
        return $this->surveyResponse;
    }

    public function setSurveyResponse(?InternationalSurveyResponse $surveyResponse): self
    {
        $this->surveyResponse = $surveyResponse;

        return $this;
    }

    public function getTrip(): ?InternationalTrip
    {
        return $this->trip;
    }

    public function setTrip(InternationalTrip $trip): self
    {
        $this->trip = $trip;

        // set the owning side of the relation if necessary
        if ($trip->getVehicle() !== $this) {
            $trip->setVehicle($this);
        }

        return $this;
    }
}
