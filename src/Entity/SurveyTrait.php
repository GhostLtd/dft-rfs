<?php

namespace App\Entity;

use App\Entity\LongAddress; // PHPstorm indicates this isn't needed, but it is
use App\Entity\Feedback; // PHPstorm indicates this isn't needed, but it is
use App\Form\Validator as AppAssert;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SurveyTrait
{
    use IdTrait;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $invitationSentDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $firstReminderSentDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $secondReminderSentDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $latestManualReminderSentDate;

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

    public function getInvitationSentDate(): ?DateTime
    {
        return $this->invitationSentDate;
    }

    public function setInvitationSentDate(?DateTime $invitationSentDate): self
    {
        $this->invitationSentDate = $invitationSentDate;
        return $this;
    }

    public function getLatestManualReminderSentDate(): ?DateTime
    {
        return $this->latestManualReminderSentDate;
    }

    public function setLatestManualReminderSentDate(?DateTime $latestManualReminderSentDate): self
    {
        $this->latestManualReminderSentDate = $latestManualReminderSentDate;
        return $this;
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

    public function getInvitationAddress(): ?LongAddress
    {
        return $this->invitationAddress ?? null;
    }

    public function setInvitationAddress(?LongAddress $invitationAddress): self
    {
        $this->invitationAddress = $invitationAddress;
        return $this;
    }

    public function setFirstReminderSentDate(?\DateTime $firstReminderSentDate): self
    {
        $this->firstReminderSentDate = $firstReminderSentDate;
        return $this;
    }

    public function getFirstReminderSentDate(): ?\DateTime
    {
        return $this->firstReminderSentDate;
    }

    public function setSecondReminderSentDate(?\DateTime $secondReminderSentDate): self
    {
        $this->secondReminderSentDate = $secondReminderSentDate;
        return $this;
    }

    public function getSecondReminderSentDate(): ?\DateTime
    {
        return $this->secondReminderSentDate;
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
        if (!$this->invitationEmails) {
            return [];
        }

        $emails = explode(',', $this->invitationEmails);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, fn(string $email) => $email !== '');
        return array_values(array_unique($emails));
    }

    public function hasValidInvitationEmails(): bool
    {
        return count($this->getArrayOfInvitationEmails()) > 0;
    }
}
