<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait SurveyResponseTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\NotBlank(groups={"business_details"}, message="common.survey-response.business-nature.not-blank")
     */
    private $businessNature;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="common.survey-response.name.not-blank", groups={"contact_details"})
     */
    private $contactName;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\NotBlank(message="common.telephone.not-blank", groups={"contact_details"})
     */
    private $contactTelephone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="common.email.not-blank", groups={"contact_details"})
     * @Assert\Email(message="common.email.invalid", groups={"contact_details"})
     */
    private $contactEmail;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\NotNull(message="common.choice.not-null", groups={"business_details"})
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