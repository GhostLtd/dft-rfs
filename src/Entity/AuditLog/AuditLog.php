<?php

namespace App\Entity\AuditLog;

use App\Entity\IdTrait;
use App\Repository\AuditLog\AuditLogRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
class AuditLog
{
    use IdTrait;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private ?string $category = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $username = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $entityId = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $entityClass = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $timestamp = null;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getTimestamp(): ?DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
}
