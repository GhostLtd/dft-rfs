<?php

namespace App\Entity\PreEnquiry;

use App\Entity\LongAddress;
use App\Form\Validator as AppAssert;
use App\Repository\PreEnquiry\PreEnquiryResponseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=PreEnquiryResponseRepository::class)
 * @ORM\Table(name="pre_enquiry_response")
 */
class PreEnquiryResponse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isCorrectCompanyName;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(groups={"correspondence_address"})
     */
    private $isCorrectAddress;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $companyName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"correspondence_details"}, message="pre-enquiry.pre-enquiry-response.contact-name")
     */
    private $contactName;

    /**
     * @ORM\Embedded(class=LongAddress::class)
     * @AppAssert\ValidAddress(groups={"correspondence_address"}, validatePostcode=true)
     */
    private $contactAddress;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(groups={"correspondence_details"}, message="pre-enquiry.pre-enquiry-response.contact-telephone")
     */
    private $contactTelephone;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"correspondence_details"}, message="pre-enquiry.pre-enquiry-response.contact-email")
     * @Assert\Email(groups={"correspondence_details"})
     */
    private $contactEmail;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(groups={"vehicle_questions"}, message="pre-enquiry.pre-enquiry-response.vehicle-count")
     * @Assert\PositiveOrZero(groups={"vehicle_questions"})
     */
    private $totalVehicleCount;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(groups={"vehicle_questions"}, message="pre-enquiry.pre-enquiry-response.international-journey-vehicle-count.not-blank")
     * @Assert\PositiveOrZero(groups={"vehicle_questions"})
     */
    private $internationalJourneyVehicleCount;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\NotBlank(groups={"employees_and_international_journeys"}, message="pre-enquiry.pre-enquiry-response.number-of-employees")
     * @Assert\Length(max=20, maxMessage="common.string.max-length", groups={"employees_and_international_journeys"})
     */
    private $numberOfEmployees;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(groups={"employees_and_international_journeys"}, message="pre-enquiry.pre-enquiry-response.annual-journey-estimate")
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
    public function validateVehicleDetails(ExecutionContextInterface $context)
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

    /**
     * @Assert\Callback(groups={"company_name"})
     */
    public function validateCompanyName(ExecutionContextInterface $context)
    {
        if ($this->isCorrectCompanyName === null) {
            $context
                ->buildViolation('pre-enquiry.pre-enquiry-response.is-correct-company-name.not-blank')
                ->atPath('isCorrectCompanyName')
                ->addViolation();
        } else {
            if ($this->companyName === null) {
                $context
                    ->buildViolation('pre-enquiry.pre-enquiry-response.company-name.not-blank')
                    ->atPath('companyName')
                    ->addViolation();
            }
        }
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getIsCorrectCompanyName(): ?bool
    {
        return $this->isCorrectCompanyName;
    }

    public function setIsCorrectCompanyName(?bool $isCorrectCompanyName): self
    {
        $this->isCorrectCompanyName = $isCorrectCompanyName;
        return $this;
    }

    public function getIsCorrectAddress(): ?bool
    {
        return $this->isCorrectAddress;
    }

    public function setIsCorrectAddress(?bool $isCorrectAddress): self
    {
        $this->isCorrectAddress = $isCorrectAddress;
        return $this;
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

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): self
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getContactAddress(): ?LongAddress
    {
        return $this->contactAddress;
    }

    public function setContactAddress(?LongAddress $contactAddress): self
    {
        $this->contactAddress = $contactAddress;

        return $this;
    }

    public function getContactTelephone(): ?string
    {
        return $this->contactTelephone;
    }

    public function setContactTelephone(?string $contactTelephone): self
    {
        $this->contactTelephone = $contactTelephone;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

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

    public function getNumberOfEmployees(): ?string
    {
        return $this->numberOfEmployees;
    }

    public function setNumberOfEmployees(?string $numberOfEmployees): self
    {
        $this->numberOfEmployees = $numberOfEmployees;
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

    public function mergeInitialDetails(PreEnquiryResponse $response)
    {
        $this->setAnnualJourneyEstimate($response->getAnnualJourneyEstimate());
        $this->setIsCorrectCompanyName($response->getIsCorrectCompanyName());
        $this->setCompanyName($response->getCompanyName());
        $this->setIsCorrectAddress($response->getIsCorrectAddress());
        $this->setContactAddress($response->getContactAddress());
        $this->setContactName($response->getContactName());
        $this->setContactEmail($response->getContactEmail());
        $this->setInternationalJourneyVehicleCount($response->getInternationalJourneyVehicleCount());
        $this->setContactTelephone($response->getContactTelephone());
        $this->setTotalVehicleCount($response->getTotalVehicleCount());
        $this->setNumberOfEmployees($response->getNumberOfEmployees());
    }
}
