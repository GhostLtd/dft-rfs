<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

trait IdTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @ignore
     * @internal
     *
     * Should not be used outside of tests/fixtures.
     */
    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }
}
