<?php

namespace App\Entity\Domestic;

use App\Repository\Domestic\StopDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StopDayRepository::class)
 */
class Day
{
    const TRANSFERRED = 1;
    const TRANSFERRED_NONE = 2;
    const TRANSFERRED_PORT = 4;
    const TRANSFERRED_RAIL = 8;
    const TRANSFERRED_AIR = 16;

    const TRANSFER_TRANSLATION_PREFIX = 'survey.domestic.transferred-port.';
    const TRANSFER_CHOICES = [
        self::TRANSFER_TRANSLATION_PREFIX . self::TRANSFERRED_PORT => self::TRANSFERRED_PORT,
        self::TRANSFER_TRANSLATION_PREFIX . self::TRANSFERRED_RAIL => self::TRANSFERRED_RAIL,
        self::TRANSFER_TRANSLATION_PREFIX . self::TRANSFERRED_AIR => self::TRANSFERRED_AIR,
        self::TRANSFER_TRANSLATION_PREFIX . self::TRANSFERRED_NONE => self::TRANSFERRED_NONE,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=SurveyResponse::class, inversedBy="stopDays")
     * @ORM\JoinColumn(nullable=false)
     */
    private $response;

    /**
     * @ORM\Column(type="smallint")
     */
    private $day;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hasMoreThanFiveStops;

    /**
     * @ORM\OneToMany(targetEntity=DayStop::class, mappedBy="day", orphanRemoval=true)
     */
    private $stops;

    /**
     * @ORM\OneToOne(targetEntity=DaySummary::class, mappedBy="day", cascade={"persist", "remove"})
     */
    private $summary;

    public function __construct()
    {
        $this->stops = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResponse(): ?SurveyResponse
    {
        return $this->response;
    }

    public function setResponse(?SurveyResponse $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getHasMoreThanFiveStops(): ?bool
    {
        return $this->hasMoreThanFiveStops;
    }

    public function setHasMoreThanFiveStops(bool $hasMoreThanFiveStops): self
    {
        $this->hasMoreThanFiveStops = $hasMoreThanFiveStops;

        return $this;
    }

    /**
     * @return Collection|DayStop[]
     */
    public function getStops(): Collection
    {
        return $this->stops;
    }

    public function addStop(DayStop $stop): self
    {
        if (!$this->stops->contains($stop)) {
            $this->stops[] = $stop;
            $stop->setDay($this);
        }

        return $this;
    }

    public function removeStop(DayStop $stop): self
    {
        if ($this->stops->removeElement($stop)) {
            // set the owning side to null (unless already changed)
            if ($stop->getDay() === $this) {
                $stop->setDay(null);
            }
        }

        return $this;
    }

    public function getSummary(): ?DaySummary
    {
        return $this->summary;
    }

    public function setSummary(DaySummary $summary): self
    {
        $this->summary = $summary;

        // set the owning side of the relation if necessary
        if ($summary->getDay() !== $this) {
            $summary->setDay($this);
        }

        return $this;
    }
}
