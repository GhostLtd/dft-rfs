<?php

namespace App\Entity;

use App\Entity\Domestic\SurveyNote as DomesticSurveyNote;
use App\Entity\International\SurveyNote as InternationalSurveyNote;
use DateTime;
use Doctrine\Common\Collections\Collection;

interface SurveyInterface
{
    const STATE_NEW = 'new';
    const STATE_INVITATION_PENDING = 'invitation pending';
    const STATE_INVITATION_SENT = 'invited';
    const STATE_INVITATION_FAILED = 'invitation failed';
    const STATE_IN_PROGRESS = 'in-progress';
    const STATE_CLOSED = 'closed';
    const STATE_APPROVED = 'approved';
    const STATE_REJECTED = 'rejected';
    const STATE_EXPORTING = 'exporting';
    const STATE_EXPORTED = 'exported';

    public const ACTIVE_STATES = [
        self::STATE_INVITATION_SENT,
        self::STATE_INVITATION_PENDING,
        self::STATE_INVITATION_FAILED,
        self::STATE_IN_PROGRESS,
        self::STATE_NEW,
    ];

    public function getId(): ?string;
    public function getPasscodeUser(): ?PasscodeUser;
    public function setPasscodeUser(?PasscodeUser $passcodeUser): self;
    public function getInvitationSentDate(): ?DateTime;
    public function setInvitationSentDate(?DateTime $invitationSentDate): self;
    public function getResponseStartDate(): ?DateTime;
    public function getSubmissionDate(): ?DateTime;
    public function setSubmissionDate(?DateTime $submissionDate): self;
    public function getState(): ?string;
    public function setState($state): self;
    public function getInvitationEmails(): ?string;
    public function setInvitationEmails(?string $invitationEmails): self;
    public function getInvitationAddress(): ?LongAddress;
    public function setInvitationAddress(?LongAddress $invitationAddress): self;

    public function getFirstReminderSentDate(): ?DateTime;
    public function setFirstReminderSentDate(?DateTime $firstReminderSentDate): self;
    public function getSecondReminderSentDate(): ?DateTime;
    public function setSecondReminderSentDate(?DateTime $secondReminderSentDate): self;
    public function getLatestManualReminderSentDate(): ?DateTime;
    public function setLatestManualReminderSentDate(?DateTime $latestManualReminderSentDate): self;

    public function getFeedback(): ?Feedback;
    public function setFeedback(?Feedback $feedback): self;

    /** @return ?Collection<DomesticSurveyNote|InternationalSurveyNote> */
    public function getNotes(): ?Collection;
    public function getChasedCount(): int;
    public function getArrayOfInvitationEmails(): array;
    public function hasValidInvitationEmails(): bool;
}