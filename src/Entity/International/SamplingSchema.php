<?php

namespace App\Entity\International;

use App\Repository\International\SamplingSchemaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity(repositoryClass: SamplingSchemaRepository::class)]
class SamplingSchema
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $sizeGroup = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $weekNumber = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: SamplingSchemaDay::class)]
    private ?SamplingSchemaDay $day = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSizeGroup(): ?int
    {
        return $this->sizeGroup;
    }

    public function setSizeGroup(int $sizeGroup): self
    {
        $this->sizeGroup = $sizeGroup;
        return $this;
    }

    public function getWeekNumber(): ?int
    {
        return $this->weekNumber;
    }

    public function setWeekNumber(int $weekNumber): self
    {
        $this->weekNumber = $weekNumber;
        return $this;
    }

    public function getDay(): ?SamplingSchemaDay
    {
        return $this->day;
    }

    public function setDay(?SamplingSchemaDay $day): self
    {
        $this->day = $day;
        return $this;
    }
}
