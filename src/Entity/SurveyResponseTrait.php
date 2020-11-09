<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait SurveyResponseTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $businessNature;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $contactName;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $contactTelephone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $contactEmail;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBusinessNature(): ?string
    {
        return $this->businessNature;
    }

    public function setBusinessNature(string $businessNature): self
    {
        $this->businessNature = $businessNature;

        return $this;
    }


    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(string $contactName): self
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getContactTelephone(): ?string
    {
        return $this->contactTelephone;
    }

    public function setContactTelephone(string $contactTelephone): self
    {
        $this->contactTelephone = $contactTelephone;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }
}