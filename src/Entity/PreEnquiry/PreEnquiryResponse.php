<?php

namespace App\Entity\PreEnquiry;

use App\Entity\Embeddable\LongAddress;
use App\Entity\IdTrait;
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

    // HGV fields
    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.vehicle-count', groups: ['hgv_details'])]
    #[Assert\PositiveOrZero(groups: ['hgv_details'])]
    #[ORM\Column(name: 'total_hgv_count', type: Types::INTEGER, nullable: true)]
    private ?int $totalHgvCount = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.international-journey-vehicle-count.not-blank', groups: ['hgv_details'])]
    #[Assert\PositiveOrZero(groups: ['hgv_details'])]
    #[ORM\Column(name: 'international_journey_hgv_count', type: Types::INTEGER, nullable: true)]
    private ?int $internationalJourneyHgvCount = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.annual-journey-estimate', groups: ['hgv_details'])]
    #[Assert\PositiveOrZero(groups: ['hgv_details'])]
    #[ORM\Column(name: 'annual_hgv_journey_estimate', type: Types::INTEGER, nullable: true)]
    private ?int $annualHgvJourneyEstimate = null;

    // LGV fields
    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.vehicle-count', groups: ['lgv_details'])]
    #[Assert\PositiveOrZero(groups: ['lgv_details'])]
    #[ORM\Column(name: 'total_lgv_count', type: Types::INTEGER, nullable: true)]
    private ?int $totalLgvCount = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.international-journey-vehicle-count.not-blank', groups: ['lgv_details'])]
    #[Assert\PositiveOrZero(groups: ['lgv_details'])]
    #[ORM\Column(name: 'international_journey_lgv_count', type: Types::INTEGER, nullable: true)]
    private ?int $internationalJourneyLgvCount = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.annual-journey-estimate', groups: ['lgv_details'])]
    #[Assert\PositiveOrZero(groups: ['lgv_details'])]
    #[ORM\Column(name: 'annual_lgv_journey_estimate', type: Types::INTEGER, nullable: true)]
    private ?int $annualLgvJourneyEstimate = null;

    #[Assert\NotBlank(message: 'pre-enquiry.pre-enquiry-response.number-of-employees', groups: ['business_details'])]
    #[Assert\Length(max: 20, maxMessage: 'common.string.max-length', groups: ['business_details'])]
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $numberOfEmployees = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\OneToOne(inversedBy: 'response', targetEntity: PreEnquiry::class, cascade: ['persist'])]
    private ?PreEnquiry $preEnquiry = null;

    #[Assert\Callback(groups: ['hgv_details'])]
    public function validateHgvDetails(ExecutionContextInterface $context): void
    {
        if ($this->getInternationalJourneyHgvCount() !== null &&
            $this->getTotalHgvCount() !== null &&
            $this->getInternationalJourneyHgvCount() > $this->getTotalHgvCount()) {
            $context
                ->buildViolation('pre-enquiry.pre-enquiry-response.international-journey-vehicle-count.not-more-international-than-total-vehicles')
                ->atPath('internationalJourneyHgvCount')
                ->addViolation();
        }
    }

    #[Assert\Callback(groups: ['lgv_details'])]
    public function validateLgvDetails(ExecutionContextInterface $context): void
    {
        if ($this->getInternationalJourneyLgvCount() !== null &&
            $this->getTotalLgvCount() !== null &&
            $this->getInternationalJourneyLgvCount() > $this->getTotalLgvCount()) {
            $context
                ->buildViolation('pre-enquiry.pre-enquiry-response.international-journey-vehicle-count.not-more-international-than-total-vehicles')
                ->atPath('internationalJourneyLgvCount')
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

    // HGV getters/setters
    public function getTotalHgvCount(): ?int
    {
        return $this->totalHgvCount;
    }

    public function setTotalHgvCount(?int $totalHgvCount): self
    {
        $this->totalHgvCount = $totalHgvCount;
        return $this;
    }

    public function getInternationalJourneyHgvCount(): ?int
    {
        return $this->internationalJourneyHgvCount;
    }

    public function setInternationalJourneyHgvCount(?int $internationalJourneyHgvCount): self
    {
        $this->internationalJourneyHgvCount = $internationalJourneyHgvCount;
        return $this;
    }

    public function getAnnualHgvJourneyEstimate(): ?int
    {
        return $this->annualHgvJourneyEstimate;
    }

    public function setAnnualHgvJourneyEstimate(?int $annualHgvJourneyEstimate): self
    {
        $this->annualHgvJourneyEstimate = $annualHgvJourneyEstimate;
        return $this;
    }

    // LGV getters/setters
    public function getTotalLgvCount(): ?int
    {
        return $this->totalLgvCount;
    }

    public function setTotalLgvCount(?int $totalLgvCount): self
    {
        $this->totalLgvCount = $totalLgvCount;
        return $this;
    }

    public function getInternationalJourneyLgvCount(): ?int
    {
        return $this->internationalJourneyLgvCount;
    }

    public function setInternationalJourneyLgvCount(?int $internationalJourneyLgvCount): self
    {
        $this->internationalJourneyLgvCount = $internationalJourneyLgvCount;
        return $this;
    }

    public function getAnnualLgvJourneyEstimate(): ?int
    {
        return $this->annualLgvJourneyEstimate;
    }

    public function setAnnualLgvJourneyEstimate(?int $annualLgvJourneyEstimate): self
    {
        $this->annualLgvJourneyEstimate = $annualLgvJourneyEstimate;
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

    public function getPreEnquiry(): ?PreEnquiry
    {
        return $this->preEnquiry;
    }

    public function setPreEnquiry(PreEnquiry $preEnquiry): self
    {
        $this->preEnquiry = $preEnquiry;
        return $this;
    }

    public function mergeInitialDetails(PreEnquiryResponse $response): void
    {
        // HGV fields
        $this->setTotalHgvCount($response->getTotalHgvCount());
        $this->setInternationalJourneyHgvCount($response->getInternationalJourneyHgvCount());
        $this->setAnnualHgvJourneyEstimate($response->getAnnualHgvJourneyEstimate());

        // LGV fields
        $this->setTotalLgvCount($response->getTotalLgvCount());
        $this->setInternationalJourneyLgvCount($response->getInternationalJourneyLgvCount());
        $this->setAnnualLgvJourneyEstimate($response->getAnnualLgvJourneyEstimate());

        // Other fields
        $this->setIsCorrectCompanyName($response->getIsCorrectCompanyName());
        $this->setCompanyName($response->getCompanyName());
        $this->setIsCorrectAddress($response->getIsCorrectAddress());
        $this->setContactAddress($response->getContactAddress());
        $this->setContactName($response->getContactName());
        $this->setContactEmail($response->getContactEmail());
        $this->setContactTelephone($response->getContactTelephone());
        $this->setNumberOfEmployees($response->getNumberOfEmployees());
    }
}
