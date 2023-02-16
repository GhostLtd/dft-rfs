<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;

interface SurveyStateInterface
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

    public function getState(): ?string;
    public function setState(?string $state): self;

    /** @return ?Collection<NoteInterface> */
    public function getNotes(): ?Collection;
    public function addNote(NoteInterface $note): self;
}