<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Address
{
    public function __toString()
    {
        @trigger_error('The __tostring() method is deprecated and will be removed. Use the formatAddress twig filter instead.', E_USER_DEPRECATED);
        return implode(", ", array_filter([$this->line1, $this->line2, $this->line3, $this->line4, $this->postcode]));
    }

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $line1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $line2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $line3;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $line4;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $postcode;

    public function getLine1(): ?string
    {
        return $this->line1;
    }

    public function setLine1(?string $line1): self
    {
        $this->line1 = $line1;

        return $this;
    }

    public function getLine2(): ?string
    {
        return $this->line2;
    }

    public function setLine2(?string $line2): self
    {
        $this->line2 = $line2;

        return $this;
    }

    public function getLine3(): ?string
    {
        return $this->line3;
    }

    public function setLine3(?string $line3): self
    {
        $this->line3 = $line3;

        return $this;
    }

    public function getLine4(): ?string
    {
        return $this->line4;
    }

    public function setLine4(?string $line4): self
    {
        $this->line4 = $line4;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    // -----

    public function isFilled(): bool {
        return $this->line1 || $this->line2 || $this->line3 || $this->line4 || $this->postcode;
    }
}
