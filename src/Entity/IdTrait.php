<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait IdTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
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
