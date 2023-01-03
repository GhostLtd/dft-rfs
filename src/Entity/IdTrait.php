<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait IdTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\Column(type="string", unique=true, length=36, options={"fixed" = true})
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     */
    private $id;

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