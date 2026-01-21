<?php

namespace App\Entity;

use App\Repository\EmailBlacklistRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmailBlacklistRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'This email is already blacklisted')]
class EmailBlacklist
{
    use IdTrait;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?\DateTime $createdAt = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email === null ? null : strtolower(trim($email));
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
