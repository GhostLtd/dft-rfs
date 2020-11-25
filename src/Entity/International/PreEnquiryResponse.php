<?php

namespace App\Entity\International;

use App\Entity\Address;
use App\Repository\International\PreEnquiryResponseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=PreEnquiryResponseRepository::class)
 * @ORM\Table(name="international_pre_enquiry_response")
 */
class PreEnquiryResponse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"company_name"})
     */
    private $companyName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"correspondence_details"})
     */
    private $correspondenceName;

    /**
     * @ORM\Embedded(class=Address::class)
     * @Assert\Valid(groups={"correspondence_address"})
     */
    private $correspondenceAddress;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(groups={"correspondence_details"})
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"correspondence_details"})
     * @Assert\Email(groups={"correspondence_details"})
     */
    private $email;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(groups={"vehicle_questions"})
     * @Assert\PositiveOrZero(groups={"vehicle_questions"})
     */
    private $totalVehicleCount;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(groups={"vehicle_questions"})
     * @Assert\PositiveOrZero(groups={"vehicle_questions"})
     */
    private $internationalJourneyVehicleCount;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(groups={"employees_and_international_journeys"})
     * @Assert\PositiveOrZero(groups={"employees_and_international_journeys"})
     */
    private $employeeCount;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(groups={"employees_and_international_journeys"})
     * @Assert\PositiveOrZero(groups={"employees_and_international_journeys"})
     */
    private $annualJourneyEstimate;

    /**
     * @ORM\OneToOne(targetEntity=PreEnquiry::class, inversedBy="response", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $preEnquiry;

    /**
     * @Assert\Callback(groups={"vehicle_questions"})
     */
    public function validate(ExecutionContextInterface $context)
    {
        if ($this->getInternationalJourneyVehicleCount() !== null &&
            $this->getTotalVehicleCount() !== null &&
            $this->getInternationalJourneyVehicleCount() > $this->getTotalVehicleCount()) {
            $context
                ->buildViolation('pre-enquiry.pre-enquiry-response.international-journey-vehicle-count.not-more-international-than-total-vehicles')
                ->atPath('internationalJourneyVehicleCount')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getCorrespondenceName(): ?string
    {
        return $this->correspondenceName;
    }

    public function setCorrespondenceName(?string $correspondenceName): self
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

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTotalVehicleCount(): ?int
    {
        return $this->totalVehicleCount;
    }

    public function setTotalVehicleCount(?int $totalVehicleCount): self
    {
        $this->totalVehicleCount = $totalVehicleCount;

        return $this;
    }

    public function getInternationalJourneyVehicleCount(): ?int
    {
        return $this->internationalJourneyVehicleCount;
    }

    public function setInternationalJourneyVehicleCount(?int $internationalJourneyVehicleCount): self
    {
        $this->internationalJourneyVehicleCount = $internationalJourneyVehicleCount;

        return $this;
    }

    public function getEmployeeCount(): ?int
    {
        return $this->employeeCount;
    }

    public function setEmployeeCount(?int $employeeCount): self
    {
        $this->employeeCount = $employeeCount;

        return $this;
    }

    public function getAnnualJourneyEstimate(): ?int
    {
        return $this->annualJourneyEstimate;
    }

    public function setAnnualJourneyEstimate(?int $annualJourneyEstimate): self
    {
        $this->annualJourneyEstimate = $annualJourneyEstimate;

        return $this;
    }

    public function getPreEnquiry(): ?PreEnquiry
    {
        return $this->preEnquiry;
    }

    public function setPreEnquiry(PreEnquiry $preEnquiry): self
    {
        $this->preEnquiry = $preEnquiry;

        return $this;
    }
}
