<?php

namespace App\Entity\Utility;

use App\Entity\IdTrait;
use App\Repository\MaintenanceWarningRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaintenanceWarningRepository::class)]
class MaintenanceWarning
{
    use IdTrait;

    #[Assert\NotBlank(message: 'Provide a date/time')]
    #[Assert\GreaterThan('now', message: 'Provide a date/time in the future')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $start = null;

    #[Assert\NotBlank(message: 'Provide a time')]
    #[ORM\Column(type: Types::TIME_MUTABLE)]
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
