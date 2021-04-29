<?php

namespace App\Entity;

use DateTimeInterface;

interface SurveyInterface
{
    const STATE_NEW = 'new';
    const STATE_INVITED_USER = 'invited';
    const STATE_IN_PROGRESS = 'in-progress';
    const STATE_CLOSED = 'closed';
    const STATE_APPROVED = 'approved';
    const STATE_REJECTED = 'rejected';
    const STATE_EXPORTING = 'exporting';
    const STATE_EXPORTED = 'exported';

    const STATE_CHOICES = [
        'admin.survey.state.new' => self::STATE_NEW,
        'admin.survey.state.invited' => self::STATE_INVITED_USER,
        'admin.survey.state.in-progress' => self::STATE_IN_PROGRESS,
        'admin.survey.state.closed' => self::STATE_CLOSED,
        'admin.survey.state.approved' => self::STATE_APPROVED,
        'admin.survey.state.rejected' => self::STATE_REJECTED,
        'admin.survey.state.exporting' => self::STATE_EXPORTING,
        'admin.survey.state.exported' => self::STATE_EXPORTED,
    ];

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