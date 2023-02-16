<?php

namespace App\Entity\RoRo;

use App\Entity\IdTrait;
use App\Repository\RoRo\CountryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CountryRepository::class)
 * @ORM\Table(name="roro_country")
 */
class Country
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=8, nullable=false)
     */
    private ?string $code;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }
}
