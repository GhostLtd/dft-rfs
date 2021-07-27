<?php

namespace App\Entity\Utility;

use App\Entity\IdTrait;
use App\Repository\MaintenanceWarningRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=MaintenanceWarningRepository::class)
 */
class MaintenanceWarning
{
    use IdTrait;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank(message="Provide a date/time")
     * @Assert\GreaterThan ("now", message="Provide a date/time in the future")
     */
    private ?\DateTime $start;

    /**
     * @ORM\Column(type="time")
     * @Assert\NotBlank(message="Provide a time")
     * @Assert\GreaterThan(propertyPath="startTime", message="The end must be after the start")
     */
    private ?\DateTime $end;

    public function getStart(): ?\DateTime
    {
        return $this->start;
    }

    public function setStart(?\DateTime $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        if (!$this->getStart()) return null;
        return new \DateTime($this->getStart()->format('H:i:s'));
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
