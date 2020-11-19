<?php

namespace App\Entity\International;

use App\Repository\International\SurveyResponseRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SurveyResponseRepository::class)
 * @ORM\Table(name="international_survey_response")
 */
class SurveyResponse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $annualInternationalJourneyCount;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $unableToCompleteDate;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    private $unableToCompleteReason;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, inversedBy="surveyResponse", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $survey;

    /**
     * @ORM\OneToMany(targetEntity=Vehicle::class, mappedBy="surveyResponse", orphanRemoval=true)
     */
    private $vehicles;

    /**
     * @ORM\Column(type="boolean")
     */
    private $fewerThanTenEmployees;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnnualInternationalJourneyCount(): ?int
    {
        return $this->annualInternationalJourneyCount;
    }

    public function setAnnualInternationalJourneyCount(int $annualInternationalJourneyCount): self
    {
        $this->annualInternationalJourneyCount = $annualInternationalJourneyCount;

        return $this;
    }

    public function getUnableToCompleteDate(): ?DateTimeInterface
    {
        return $this->unableToCompleteDate;
    }

    public function setUnableToCompleteDate(?DateTimeInterface $unableToCompleteDate): self
    {
        $this->unableToCompleteDate = $unableToCompleteDate;

        return $this;
    }

    public function getUnableToCompleteReason(): ?string
    {
        return $this->unableToCompleteReason;
    }

    public function setUnableToCompleteReason(?string $unableToCompleteReason): self
    {
        $this->unableToCompleteReason = $unableToCompleteReason;

        return $this;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(Survey $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    /**
     * @return Collection|Vehicle[]
     */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): self
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles[] = $vehicle;
            $vehicle->setSurveyResponse($this);
        }

        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): self
    {
        if ($this->vehicles->removeElement($vehicle)) {
            // set the owning side to null (unless already changed)
            if ($vehicle->getSurveyResponse() === $this) {
                $vehicle->setSurveyResponse(null);
            }
        }

        return $this;
    }

    public function getFewerThanTenEmployees(): ?bool
    {
        return $this->fewerThanTenEmployees;
    }

    public function setFewerThanTenEmployees(bool $fewerThanTenEmployees): self
    {
        $this->fewerThanTenEmployees = $fewerThanTenEmployees;

        return $this;
    }
}
