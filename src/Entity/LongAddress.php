<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class LongAddress extends Address
{
    #[\Override]
    public function toArray(): array
    {
        return [
            'line1' => $this->line1,
            'line2' => $this->line2,
            'line3' => $this->line3,
            'line4' => $this->line4,
            'line5' => $this->line5,
            'line6' => $this->line6,
            'postcode' => $this->postcode,
        ];
    }

    #[Assert\Regex("#^[@()=\[\]\",<>\\\/]#", message: 'Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notify_api'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $line5 = null;

    #[Assert\Regex("#^[@()=\[\]\",<>\\\/]#", message: 'Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notify_api'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $line6 = null;

    public function getLine5(): ?string
    {
        return $this->line5;
    }

    public function setLine5(?string $line5): static
    {
        $this->line5 = $line5;

        return $this;
    }

    public function getLine6(): ?string
    {
        return $this->line6;
    }

    public function setLine6(?string $line6): static
    {
        $this->line6 = $line6;

        return $this;
    }
    // -----

    #[\Override]
    public function isFilled(): bool {
        return $this->line1 || $this->line2 || $this->line5 || $this->line6 || $this->postcode;
    }

    public function getFilledLinesCount(): int {
        return boolval($this->line1) + boolval($this->line2) + boolval($this->line3) + boolval($this->line4)
            + boolval($this->line5) + boolval($this->line6) + boolval($this->postcode);
    }

    public static function createFromAddress(string $name, Address $address): LongAddress
    {
        return (new LongAddress())
            ->setLine1($name)
            ->setLine2($address->getLine1())
            ->setLine3($address->getLine2())
            ->setLine4($address->getLine3())
            ->setLine5($address->getLine4())
            ->setPostcode($address->getPostcode());
    }
}
