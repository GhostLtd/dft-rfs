<?php

namespace App\Entity;

use DateTimeInterface;

interface SurveyInterface
{
    public function getId(): ?string;
    public function getNotifiedDate(): ?DateTimeInterface;
    public function getSurveyPeriodStart(): ?DateTimeInterface;
    public function getSurveyPeriodStartModifiedBy($modifier);
    public function getSurveyPeriodEnd(): ?DateTimeInterface;
    public function getSurveyPeriodInDays(): ?int;
    public function getResponseStartDate(): ?DateTimeInterface;
    public function getSubmissionDate(): ?DateTimeInterface;
    public function getState(): string;
    public function getInvitationEmail(): ?string;
    public function getInvitationAddress(): ?LongAddress;
}