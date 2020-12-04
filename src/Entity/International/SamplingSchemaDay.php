<?php

namespace App\Entity\International;

use App\Repository\International\SamplingSchemaDayRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SamplingSchemaDayRepository::class)
 */
class SamplingSchemaDay
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
