<?php

namespace App\Entity;

use DateTime;

interface SurveyInterface
{
    public function getPasscodeUser(): ?PasscodeUser;
    public function setPasscodeUser(?PasscodeUser $passcodeUser): self;
    public function getResponseStartDate(): ?DateTime;
    public function getSubmissionDate(): ?DateTime;
    public function setSubmissionDate(?DateTime $submissionDate): self;
    public function getInvitationEmails(): ?string;
    public function setInvitationEmails(?string $invitationEmails): self;
    public function getInvitationAddress(): ?LongAddress;
    public function setInvitationAddress(?LongAddress $invitationAddress): self;

    public function getFeedback(): ?Feedback;
    public function setFeedback(?Feedback $feedback): self;

    public function getChasedCount(): int;
}