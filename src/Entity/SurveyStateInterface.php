<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;

interface SurveyStateInterface
{
    public const STATE_NEW = 'new';
    public const STATE_INVITATION_PENDING = 'invitation pending';
    public const STATE_INVITATION_SENT = 'invited';
    public const STATE_INVITATION_FAILED = 'invitation failed';
    public const STATE_IN_PROGRESS = 'in-progress';
    public const STATE_CLOSED = 'closed';
    public const STATE_APPROVED = 'approved';
    public const STATE_REJECTED = 'rejected';
    public const STATE_EXPORTING = 'exporting';
    public const STATE_EXPORTED = 'exported';

    public const ACTIVE_STATES = [
        self::STATE_INVITATION_SENT,
        self::STATE_INVITATION_PENDING,
        self::STATE_INVITATION_FAILED,
        self::STATE_IN_PROGRESS,
        self::STATE_NEW,
    ];

    public function getId(): ?string;

    public function getState(): ?string;
    public function setState(?string $state): self;

    /** @return ?Collection<NoteInterface> */
    public function getNotes(): ?Collection;
    public function addNote(NoteInterface $note): self;
}