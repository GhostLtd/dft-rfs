<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SurveyResponseTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"business_details", "admin_business_details"}, message="common.survey-response.business-nature.not-blank")
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"business_details", "admin_business_details"})
     */
    private $businessNature;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="common.survey-response.name.not-blank", groups={"contact_details", "admin_correspondence"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"contact_details", "admin_correspondence"})
     */
    private $contactName;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50, maxMessage="common.string.max-length", groups={"contact_details", "admin_correspondence"})
     */
    private $contactTelephone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Email(message="common.email.invalid", groups={"contact_details", "admin_correspondence"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"contact_details", "admin_correspondence"})
     */
    private $contactEmail;

    /**
     * @Assert\Callback(groups={"contact_details", "admin_correspondence"})
     */
    public function validInvitationDetails(ExecutionContextInterface $context) {
        if (!$this->contactTelephone && !$this->contactEmail) {
            $context
                ->buildViolation('domestic.survey-response.initial-details.contact-email-or-telephone-required')
                ->atPath('contactTelephone')
                ->addViolation();
            $context
                ->buildViolation('domestic.survey-response.initial-details.contact-email-or-telephone-required')
                ->atPath('contactEmail')
                ->addViolation();
        }
    }


    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\NotNull(message="domestic.survey-response.number-of-employees.choice", groups={"business_details", "admin_business_details"})
     * @Assert\Length(max=20, maxMessage="common.string.max-length", groups={"business_details", "admin_business_details"})
     */
    private $numberOfEmployees;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getBusinessNature(): ?string
    {
        return $this->businessNature;
    }

    public function setBusinessNature(?string $businessNature): self
    {
        $this->businessNature = $businessNature;

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

    public function getNumberOfEmployees(): ?string
    {
        return $this->numberOfEmployees;
    }

    public function setNumberOfEmployees(?string $numberOfEmployees): self
    {
        $this->numberOfEmployees = $numberOfEmployees;

        return $this;
    }
}