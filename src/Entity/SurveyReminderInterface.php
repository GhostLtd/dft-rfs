<?php

namespace App\Entity;

use DateTime;

interface SurveyReminderInterface
{
    public function getId(): ?string;

    public function getFirstReminderSentDate(): ?DateTime;
    public function setFirstReminderSentDate(?DateTime $firstReminderSentDate): self;
    public function getSecondReminderSentDate(): ?DateTime;
    public function setSecondReminderSentDate(?DateTime $secondReminderSentDate): self;
    public function getLatestManualReminderSentDate(): ?DateTime;
    public function setLatestManualReminderSentDate(?DateTime $latestManualReminderSentDate): self;
    public function getInvitationSentDate(): ?DateTime;
    public function setInvitationSentDate(?DateTime $invitationSentDate): self;

    public function getInvitationAddress(): ?LongAddress;
    public function getArrayOfInvitationEmails(): array;
    public function hasValidInvitationEmails(): bool;
}