<?php

namespace App\Entity\Utility;

use App\Entity\IdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class MaintenanceWarning
{
    use IdTrait;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank(message="Provide a date and time")
     */
    private ?\DateTime $start = null;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank(message="Provide a date and time")
     * @Assert\Expression("is_empty(value) || value > this.getStart()", message="The end must be after the start")
     */
    private ?\DateTime $end = null;

    public function getStart(): ?\DateTime
    {
        return $this->start;
    }

    public function setStart(?\DateTime $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    public function setEnd(?\DateTime $end): self
    {
        $this->end = $end;

        return $this;
    }
}
