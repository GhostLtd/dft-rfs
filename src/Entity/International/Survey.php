<?php

namespace App\Entity\International;

use App\Entity\PasscodeUser;
use App\Entity\SurveyTrait;
use App\Repository\International\SurveyRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SurveyRepository::class)
 * @ORM\Table(name="international_survey")
 */
class Survey
{
    use SurveyTrait;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $referenceNumber;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $dueDate;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="surveys")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\OneToOne(targetEntity=SurveyResponse::class, mappedBy="survey", cascade={"persist"})
     */
    private $response;

    /**
     * @ORM\OneToOne(targetEntity=PasscodeUser::class, mappedBy="internationalSurvey", cascade={"persist", "remove"})
     */
    private $passcodeUser;

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(string $referenceNumber): self
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    public function getDueDate(): ?DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getResponse(): ?SurveyResponse
    {
        return $this->response;
    }

    public function setResponse(SurveyResponse $response): self
    {
        $this->response = $response;

        // set the owning side of the relation if necessary
        if ($response->getSurvey() !== $this) {
            $response->setSurvey($this);
        }

        return $this;
    }

    public function getPasscodeUser(): ?PasscodeUser
    {
        return $this->passcodeUser;
    }

    public function setPasscodeUser(?PasscodeUser $passcodeUser): self
    {
        $this->passcodeUser = $passcodeUser;

        // set (or unset) the owning side of the relation if necessary
        $newInternationalSurvey = null === $passcodeUser ? null : $this;
        if ($passcodeUser->getInternationalSurvey() !== $newInternationalSurvey) {
            $passcodeUser->setInternationalSurvey($newInternationalSurvey);
        }

        return $this;
    }

    public function isInitialDetailsComplete(): bool
    {
        return !!$this->response;
    }
}
