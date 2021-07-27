<?php

namespace App\Entity\International;

use App\Repository\International\SamplingSchemaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SamplingSchemaRepository::class)
 */
class SamplingSchema
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     */
    private $sizeGroup;

    /**
     * @ORM\Column(type="smallint")
     */
    private $weekNumber;

    /**
     * @ORM\ManyToOne(targetEntity=SamplingSchemaDay::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $day;

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
