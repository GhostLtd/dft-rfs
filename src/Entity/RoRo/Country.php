<?php

namespace App\Entity\RoRo;

use App\Entity\IdTrait;
use App\Repository\RoRo\CountryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'roro_country')]
#[ORM\Entity(repositoryClass: CountryRepository::class)]
class Country
{
    use IdTrait;

    #[ORM\Column(type: Types::STRING, length: 8, nullable: false)]
    private ?string $code = null;

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
