<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Address implements \Stringable
{
    public function toArray(): array
    {
        return [
            'line1' => $this->line1,
            'line2' => $this->line2,
            'line3' => $this->line3,
            'line4' => $this->line4,
            'postcode' => $this->postcode,
        ];
    }

    #[Assert\Regex("#^[@()=\[\]\",<>\\\/]#", message: 'Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notify_api'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $line1 = null;

    #[Assert\Regex("#^[@()=\[\]\",<>\\\/]#", message: 'Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notify_api'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $line3 = null;

    #[Assert\Regex("#^[@()=\[\]\",<>\\\/]#", message: 'Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notify_api'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $line2 = null;

    #[Assert\Regex("#^[@()=\[\]\",<>\\\/]#", message: 'Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notify_api'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $line4 = null;

    #[Assert\Regex("#^[@()=\[\]\",<>\\\/]#", message: 'Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notify_api'])]
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    protected ?string $postcode = null;

    public function getLine1(): ?string
    {
        return $this->line1;
    }

    public function setLine1(?string $line1): static
    {
        $this->line1 = $line1;
        return $this;
    }

    public function getLine2(): ?string
    {
        return $this->line2;
    }

    public function setLine2(?string $line2): static
    {
        $this->line2 = $line2;
        return $this;
    }

    public function getLine3(): ?string
    {
        return $this->line3;
    }

    public function setLine3(?string $line3): static
    {
        $this->line3 = $line3;
        return $this;
    }

    public function getLine4(): ?string
    {
        return $this->line4;
    }

    public function setLine4(?string $line4): static
    {
        $this->line4 = $line4;
        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): static
    {
        $this->postcode = $postcode;
        return $this;
    }

    // -----

    public function isFilled(): bool {
        return $this->line1 || $this->line2 || $this->line3 || $this->line4 || $this->postcode;
    }

    #[\Override]
    public function __toString(): string {
        $nonEmptyLines = array_filter($this->toArray(), fn(?string $line) => $line && trim($line) !== '');
        return join(', ', $nonEmptyLines);
    }
}
