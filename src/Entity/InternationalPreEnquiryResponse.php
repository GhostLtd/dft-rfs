<?php

namespace App\Entity;

use App\Repository\InternationalPreEnquiryResponseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternationalPreEnquiryResponseRepository::class)
 */
class InternationalPreEnquiryResponse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $companyName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $correspondenceName;

    /**
     * @ORM\Embedded(class="App\Entity\Address")
     */
    private $correspondenceAddress;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="integer")
     */
    private $totalVehicleCount;

    /**
     * @ORM\Column(type="integer")
     */
    private $internationalJourneyVehicleCount;

    /**
     * @ORM\Column(type="integer")
     */
    private $employeeCount;

    /**
     * @ORM\Column(type="integer")
     */
    private $annualJourneyEstimate;

    /**
     * @ORM\OneToOne(targetEntity=InternationalPreEnquiry::class, inversedBy="response", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $preEnquiry;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getCorrespondenceName(): ?string
    {
        return $this->correspondenceName;
    }

    public function setCorrespondenceName(string $correspondenceName): self
    {
        $this->correspondenceName = $correspondenceName;

        return $this;
    }

    public function getCorrespondenceAddress(): ?Address
    {
        return $this->correspondenceAddress;
    }

    public function setCorrespondenceAddress(Address $correspondenceAddress): self
    {
        $this->correspondenceAddress = $correspondenceAddress;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTotalVehicleCount(): ?int
    {
        return $this->totalVehicleCount;
    }

    public function setTotalVehicleCount(int $totalVehicleCount): self
    {
        $this->totalVehicleCount = $totalVehicleCount;

        return $this;
    }

    public function getInternationalJourneyVehicleCount(): ?int
    {
        return $this->internationalJourneyVehicleCount;
    }

    public function setInternationalJourneyVehicleCount(int $internationalJourneyVehicleCount): self
    {
        $this->internationalJourneyVehicleCount = $internationalJourneyVehicleCount;

        return $this;
    }

    public function getEmployeeCount(): ?int
    {
        return $this->employeeCount;
    }

    public function setEmployeeCount(int $employeeCount): self
    {
        $this->employeeCount = $employeeCount;

        return $this;
    }

    public function getAnnualJourneyEstimate(): ?int
    {
        return $this->annualJourneyEstimate;
    }

    public function setAnnualJourneyEstimate(int $annualJourneyEstimate): self
    {
        $this->annualJourneyEstimate = $annualJourneyEstimate;

        return $this;
    }

    public function getPreEnquiry(): ?InternationalPreEnquiry
    {
        return $this->preEnquiry;
    }

    public function setPreEnquiry(InternationalPreEnquiry $preEnquiry): self
    {
        $this->preEnquiry = $preEnquiry;

        return $this;
    }
}