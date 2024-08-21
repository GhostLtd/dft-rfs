<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;

interface SurveyNotesInterface
{
    public function addNote(NoteInterface $note): self;
    public function createNote(): NoteInterface;

    /** @return ?Collection<NoteInterface> */
    public function getNotes(): ?Collection;

    /** @param $notes Collection<NoteInterface> */
    public function setNotes(Collection $notes): self;

    public function getChasedCount(): int;
}
