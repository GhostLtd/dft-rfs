<?php

namespace App\Entity;

use DateTime;

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

    const STATE_CHOICES = [
        'admin.survey.state.new' => self::STATE_NEW,
        'admin.survey.state.invitation pending' => self::STATE_INVITATION_PENDING,
        'admin.survey.state.invited' => self::STATE_INVITATION_SENT,
        'admin.survey.state.invitation failed' => self::STATE_INVITATION_FAILED,
        'admin.survey.state.in-progress' => self::STATE_IN_PROGRESS,
        'admin.survey.state.closed' => self::STATE_CLOSED,
        'admin.survey.state.approved' => self::STATE_APPROVED,
        'admin.survey.state.rejected' => self::STATE_REJECTED,
        'admin.survey.state.exporting' => self::STATE_EXPORTING,
        'admin.survey.state.exported' => self::STATE_EXPORTED,
    ];

    public const ACTIVE_STATES = [
        self::STATE_INVITATION_SENT,
        self::STATE_INVITATION_PENDING,
        self::STATE_INVITATION_FAILED,
        self::STATE_IN_PROGRESS,
        self::STATE_NEW,
    ];

    public function getId(): ?string;
    public function getPasscodeUser(): ?PasscodeUser;
    public function getNotifiedDate(): ?DateTime;
    public function setNotifiedDate(?DateTime $notifiedDate): self;
    public function getResponseStartDate(): ?DateTime;
    public function getSubmissionDate(): ?DateTime;
    public function setSubmissionDate(?DateTime $submissionDate): self;
    public function getState(): string;
    public function getInvitationEmail(): ?string;
    public function getInvitationAddress(): ?LongAddress;

    public function getFirstReminderSentDate(): ?\DateTime;
    public function setFirstReminderSentDate(?\DateTime $firstReminderSentDate): self;
    public function getSecondReminderSentDate(): ?\DateTime;
    public function setSecondReminderSentDate(?\DateTime $secondReminderSentDate): self;
}