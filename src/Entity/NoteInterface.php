<?php

namespace App\Entity;

interface NoteInterface
{
    public function getNote(): ?string;
    public function setNote(?string $note): self;
    public function setCreatedBy(?string $createdBy): self;
    public function getCreatedBy(): ?string;
    public function setCreatedAt(?\DateTime $createdAt): self;
    public function getCreatedAt(): ?\DateTime;
    public function getWasChased(): ?bool;
    public function setWasChased(?bool $wasChased): self;
}