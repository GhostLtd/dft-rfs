<?php

namespace App\Entity;

use DateTime;

interface SurveyInterface extends SurveyStateInterface
{
    public function getPasscodeUser(): ?PasscodeUser;
    public function setPasscodeUser(?PasscodeUser $passcodeUser): self;
    public function getInvitationSentDate(): ?DateTime;
    public function setInvitationSentDate(?DateTime $invitationSentDate): self;
    public function getResponseStartDate(): ?DateTime;
    public function getSubmissionDate(): ?DateTime;
    public function setSubmissionDate(?DateTime $submissionDate): self;
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

    public function getChasedCount(): int;
    public function getArrayOfInvitationEmails(): array;
    public function hasValidInvitationEmails(): bool;
}