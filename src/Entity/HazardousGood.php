<?php

namespace App\Entity;

use App\Repository\HazardousGoodRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HazardousGoodRepository::class)
 */
class HazardousGood
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     */
    private $codeMajor;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $codeMinor;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeMajor(): ?int
    {
        return $this->codeMajor;
    }

    public function setCodeMajor(int $codeMajor): self
    {
        $this->codeMajor = $codeMajor;

        return $this;
    }

    public function getCodeMinor(): ?int
    {
        return $this->codeMinor;
    }

    public function setCodeMinor(?int $codeMinor): self
    {
        $this->codeMinor = $codeMinor;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
