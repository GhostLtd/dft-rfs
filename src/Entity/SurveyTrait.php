<?php

namespace App\Entity;

use App\Entity\Embeddable\LongAddress;
use App\Form\Validator as AppAssert;
use App\Utility\EmailHelper;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SurveyTrait
{
    use IdTrait;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $responseStartDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $submissionDate;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: false)]
    private ?string $state;

    #[Assert\Length(max: 1024, maxMessage: 'common.string.max-length', groups: ['add_survey', 'import_survey'])]
    #[AppAssert\CommaSeparatedEmails(message: "common.email.list-contains-invalid", groups: ["add_survey", "import_survey"])]
    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true)]
    private ?string $invitationEmails = null;

    // Non-persisted property, used during DVLA/DVSA import, and invite/reminder sending
    private ?NotificationInterception $transientNotificationInterception = null;

    #[Assert\Valid(groups: ['add_srvuey', 'import_survey', 'notify_api'])]
    #[AppAssert\ValidAddress(groups: ["add_survey", "import_survey"], validatePostcode: true, allowBlank: true)]
    #[ORM\Embedded(class: LongAddress::class)]
    private ?LongAddress $invitationAddress;

    #[ORM\OneToOne(targetEntity: Feedback::class, cascade: ['persist', 'remove'])]
    private ?Feedback $feedback;

    #[Assert\Callback(groups: ['add_survey.not-enabled'])]
    public function validInvitationDetails(ExecutionContextInterface $context): void
    {
        if (!$this->invitationEmails && (!$this->invitationAddress || !$this->invitationAddress->isFilled())) {
            $context
                ->buildViolation('international.add.invitation-email-or-address')
                ->atPath('invitationEmails')
                ->addViolation();
            // need to add the context to both, because email address is disabled (and therefore won't trip the form->isValid check)
            $context
                ->buildViolation('international.add.invitation-email-or-address')
                ->atPath('invitationAddress')
                ->addViolation();
        }
    }

    public function getResponseStartDate(): ?DateTime
    {
        return $this->responseStartDate;
    }

    public function setResponseStartDate(?DateTime $responseStartDate): self
    {
        $this->responseStartDate = $responseStartDate;
        return $this;
    }

    public function getSubmissionDate(): ?DateTime
    {
        return $this->submissionDate;
    }

    public function setSubmissionDate(?DateTime $submissionDate): self
    {
        $this->submissionDate = $submissionDate;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state ?? null;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getInvitationEmails(): ?string
    {
        return $this->invitationEmails ?? null;
    }

    public function setInvitationEmails(?string $invitationEmails): self
    {
        $this->invitationEmails = $invitationEmails;
        return $this;
    }

    public function getTransientNotificationInterception(): ?NotificationInterception
    {
        return $this->transientNotificationInterception;
    }

    public function setTransientNotificationInterception(?NotificationInterception $transientNotificationInterception): self
    {
        $this->transientNotificationInterception = $transientNotificationInterception;
        return $this;
    }

    public function getInvitationAddress(): ?LongAddress
    {
        return $this->invitationAddress ?? null;
    }

    public function setInvitationAddress(?LongAddress $invitationAddress): self
    {
        $this->invitationAddress = $invitationAddress;
        return $this;
    }

    public function hasInvitationAddress(): bool
    {
        return ($this->invitationAddress instanceof LongAddress) && $this->invitationAddress->isFilled();
    }

    public function getFeedback(): ?Feedback
    {
        return $this->feedback ?? null;
    }

    public function setFeedback(?Feedback $feedback): self
    {
        $this->feedback = $feedback;
        return $this;
    }

    // -----

    /**
     * @return array<string>
     */
    public function getArrayOfInvitationEmails(): array
    {
        return EmailHelper::convertStringListToArray($this->invitationEmails);
    }

    public function hasValidInvitationEmails(): bool
    {
        return count($this->getArrayOfInvitationEmails()) > 0;
    }
}
