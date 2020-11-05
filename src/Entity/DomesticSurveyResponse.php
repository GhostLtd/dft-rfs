<?php

namespace App\Entity;

use App\Repository\DomesticSurveyResponseRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DomesticSurveyResponseRepository::class)
 */
class DomesticSurveyResponse
{
    const UNABLE_TO_COMPLETE_REASONS = [
        'survey.domestic.non-complete.scrapped-or-stolen' => 'scrapped-or-stolen',
        'survey.domestic.non-complete.sold' => 'sold',
        'survey.domestic.non-complete.on-hire' => 'on-hire',
        'survey.domestic.non-complete.not-taxed' => 'not-taxed',
        'survey.domestic.non-complete.no-work' => 'no-work',
        'survey.domestic.non-complete.repair' => 'repair',
        'survey.domestic.non-complete.site-work-only' => 'site-work-only',
        'survey.domestic.non-complete.holiday' => 'holiday',
        'survey.domestic.non-complete.maintenance' => 'maintenance',
        'survey.domestic.non-complete.no-driver' => 'no-driver',
        'survey.domestic.non-complete.other' => 'other',
    ];

    use SurveyResponseTrait;

    /**
     * @ORM\Column(type="integer")
     */
    private $numberOfEmployees;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hireeName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hireeEmail;

    /**
     * @ORM\Embedded(class="App\Entity\Address")
     */
    private $hireeAddress;

    /**
     * @ORM\Embedded(class="App\Entity\Address")
     */
    private $newOwnerAddress;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $unableToCompleteDate;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    private $unableToCompleteReason;

    /**
     * @ORM\OneToOne(targetEntity=DomesticSurvey::class, inversedBy="surveyResponse", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $survey;

    /**
     * @ORM\OneToOne(targetEntity=DomesticVehicle::class, cascade={"persist", "remove"})
     */
    private $vehicle;

    public function getNumberOfEmployees(): ?int
    {
        return $this->numberOfEmployees;
    }

    public function setNumberOfEmployees(int $numberOfEmployees): self
    {
        $this->numberOfEmployees = $numberOfEmployees;

        return $this;
    }

    public function getHireeName(): ?string
    {
        return $this->hireeName;
    }

    public function setHireeName(string $hireeName): self
    {
        $this->hireeName = $hireeName;

        return $this;
    }

    public function getHireeEmail(): ?string
    {
        return $this->hireeEmail;
    }

    public function setHireeEmail(string $hireeEmail): self
    {
        $this->hireeEmail = $hireeEmail;

        return $this;
    }

    public function getHireeAddress(): ?Address
    {
        return $this->hireeAddress;
    }

    public function setHireeAddress(Address $hireeAddress): self
    {
        $this->hireeAddress = $hireeAddress;

        return $this;
    }

    public function getNewOwnerAddress(): ?Address
    {
        return $this->newOwnerAddress;
    }

    public function setNewOwnerAddress(Address $newOwnerAddress): self
    {
        $this->newOwnerAddress = $newOwnerAddress;

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

    public function getSurvey(): ?DomesticSurvey
    {
        return $this->survey;
    }

    public function setSurvey(DomesticSurvey $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    public function getVehicle(): ?DomesticVehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?DomesticVehicle $vehicle): self
    {
        $this->vehicle = $vehicle;

        return $this;
    }
}
