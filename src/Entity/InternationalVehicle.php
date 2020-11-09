<?php

namespace App\Entity;

use App\Repository\InternationalVehicleRepository;
use DateTimeInterface;
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
     * @ORM\Column(type="date")
     */
    private $dateOfLeavingUK;

    /**
     * @ORM\Column(type="date")
     */
    private $dateOfReturningToUK;

    public function getSurveyResponse(): ?InternationalSurveyResponse
    {
        return $this->surveyResponse;
    }

    public function setSurveyResponse(?InternationalSurveyResponse $surveyResponse): self
    {
        $this->surveyResponse = $surveyResponse;

        return $this;
    }

    public function getDateOfLeavingUK(): ?DateTimeInterface
    {
        return $this->dateOfLeavingUK;
    }

    public function setDateOfLeavingUK(DateTimeInterface $dateOfLeavingUK): self
    {
        $this->dateOfLeavingUK = $dateOfLeavingUK;

        return $this;
    }

    public function getDateOfReturningToUK(): ?DateTimeInterface
    {
        return $this->dateOfReturningToUK;
    }

    public function setDateOfReturningToUK(DateTimeInterface $dateOfReturningToUK): self
    {
        $this->dateOfReturningToUK = $dateOfReturningToUK;

        return $this;
    }
}
