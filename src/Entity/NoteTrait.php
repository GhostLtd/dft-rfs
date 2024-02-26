<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait NoteTrait
{
    use IdTrait;

    #[Assert\NotNull(message: 'common.string.not-blank', groups: ['admin_comment'])]
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private ?string $note;

    /**
     * username of creator
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private ?string $createdBy;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?\DateTime $createdAt;

    #[Assert\NotNull(message: 'common.choice.not-null', groups: ['admin_comment'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private ?bool $wasChased;

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getWasChased(): ?bool
    {
        return $this->wasChased;
    }

    public function setWasChased(?bool $wasChased): self
    {
        $this->wasChased = $wasChased;
        return $this;
    }
}
