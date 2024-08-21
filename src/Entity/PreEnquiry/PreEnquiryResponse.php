<?php

namespace App\Entity\PreEnquiry;

use App\Entity\IdTrait;
use App\Entity\LongAddress;
use App\Form\Validator as AppAssert;
use App\Repository\PreEnquiry\PreEnquiryResponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table(name: 'pre_enquiry_response')]
#[ORM\Entity(repositoryClass: PreEnquiryResponseRepository::class)]
class PreEnquiryResponse
{
    use IdTrait;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $isCorrectCompanyName = null;

    #[Assert\NotNull(message: 'pre-enquiry.pre-enquiry-response.is-correct-address', groups: ['correspondence_address', 'is_correct_address'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $isCorrectAddress = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $companyName = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.contact-name', groups: ['correspondence_details'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $contactName = null;

    #[AppAssert\ValidAddress(groups: ["correspondence_address"], validatePostcode: true, includeAddressee: false)]
    #[ORM\Embedded(class: LongAddress::class)]
    private $contactAddress;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.contact-telephone', groups: ['correspondence_details'])]
    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $contactTelephone = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.contact-email', groups: ['correspondence_details'])]
    #[Assert\Email(groups: ['correspondence_details'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $contactEmail = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.vehicle-count', groups: ['vehicle_questions'])]
    #[Assert\PositiveOrZero(groups: ['vehicle_questions'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $totalVehicleCount = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.international-journey-vehicle-count.not-blank', groups: ['vehicle_questions'])]
    #[Assert\PositiveOrZero(groups: ['vehicle_questions'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $internationalJourneyVehicleCount = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.number-of-employees', groups: ['business_details'])]
    #[Assert\Length(max: 20, maxMessage: 'common.string.max-length', groups: ['business_details'])]
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $numberOfEmployees = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.annual-journey-estimate', groups: ['vehicle_questions'])]
    #[Assert\PositiveOrZero(groups: ['vehicle_questions'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $annualJourneyEstimate = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\OneToOne(inversedBy: 'response', targetEntity: PreEnquiry::class, cascade: ['persist'])]
    private ?PreEnquiry $preEnquiry = null;

    #[Assert\Callback(groups: ['vehicle_questions'])]
    public function validateVehicleDetails(ExecutionContextInterface $context): void
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

    #[Assert\Callback(groups: ['company_name'])]
    public function validateCompanyName(ExecutionContextInterface $context): void
    {
        if ($this->isCorrectCompanyName === null) {
            $context
                ->buildViolation('pre-enquiry.pre-enquiry-response.is-correct-company-name')
                ->atPath('isCorrectCompanyName')
                ->addViolation();
        } else {
            if ($this->companyName === null) {
                $context
                    ->buildViolation('pre-enquiry.pre-enquiry-response.company-name')
                    ->atPath('companyName')
                    ->addViolation();
            }
        }
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
