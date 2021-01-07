<?php

namespace App\Entity\International;

use App\Entity\BlameLoggable;
use App\Entity\PasscodeUser;
use App\Entity\SurveyTrait;
use App\Repository\International\SurveyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=SurveyRepository::class)
 * @ORM\Table(name="international_survey")
 */
class Survey implements BlameLoggable
{
    use SurveyTrait;

    const STATE_NEW = 'new';
    const STATE_INVITED_USER = 'invited';
    const STATE_REMINDED_USER = 'reminded';
    const STATE_IN_PROGRESS = 'in-progress';
    const STATE_CLOSED = 'closed';
    const STATE_REJECTED = 'rejected';
    const STATE_EXPORTED = 'exported';

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="common.string.not-blank", groups={"add_survey"})
     * @Assert\Length (max=255, maxMessage="common.string.max-length", groups={"add_survey"})
     */
    private $referenceNumber;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="surveys")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid(groups={"add_survey"})
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

    public function getBlameLogLabel()
    {
        return "{$this->getReferenceNumber()} started "
            . (!is_null($this->getSurveyPeriodStart()) ? $this->getSurveyPeriodStart()->format('Y-m-d') : '[unknown]');
    }

    public function getAssociatedEntityClass()
    {
        return null;
    }

    public function getAssociatedEntityId()
    {
        return null;
    }
}
