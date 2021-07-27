<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class LongAddress extends Address
{
    public function toArray()
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

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $line5;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $line6;

    public function getLine5(): ?string
    {
        return $this->line5;
    }

    public function setLine5(?string $line5): self
    {
        $this->line5 = $line5;

        return $this;
    }

    public function getLine6(): ?string
    {
        return $this->line6;
    }

    public function setLine6(?string $line6): self
    {
        $this->line6 = $line6;

        return $this;
    }
    // -----

    public function isFilled(): bool {
        return $this->line1 || $this->line2 || $this->line5 || $this->line6 || $this->postcode;
    }

    public function getFilledLinesCount(): int {
        return boolval($this->line1) + boolval($this->line2) + boolval($this->line3) + boolval($this->line4)
            + boolval($this->line5) + boolval($this->line6) + boolval($this->postcode);
    }
}
