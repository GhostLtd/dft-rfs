<?php

namespace App\Entity;

use App\Repository\DomesticStopDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DomesticStopDayRepository::class)
 */
class DomesticStopDay
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
     * @ORM\ManyToOne(targetEntity=DomesticSurveyResponse::class, inversedBy="stopDays")
     * @ORM\JoinColumn(nullable=false)
     */
    private $response;

    /**
     * @ORM\Column(type="smallint")
     */
    private $day;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasStops;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hasMoreThanFiveStops;

    /**
     * @ORM\OneToMany(targetEntity=DomesticStopMultiple::class, mappedBy="stopDay", orphanRemoval=true)
     */
    private $stops;

    /**
     * @ORM\OneToOne(targetEntity=DomesticStopSummary::class, mappedBy="stopDay", cascade={"persist", "remove"})
     */
    private $stopSummary;

    public function __construct()
    {
        $this->stops = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResponse(): ?DomesticSurveyResponse
    {
        return $this->response;
    }

    public function setResponse(?DomesticSurveyResponse $response): self
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

    public function getHasStops(): ?bool
    {
        return $this->hasStops;
    }

    public function setHasStops(bool $hasStops): self
    {
        $this->hasStops = $hasStops;

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
     * @return Collection|DomesticStopMultiple[]
     */
    public function getStops(): Collection
    {
        return $this->stops;
    }

    public function addStop(DomesticStopMultiple $stop): self
    {
        if (!$this->stops->contains($stop)) {
            $this->stops[] = $stop;
            $stop->setStopDay($this);
        }

        return $this;
    }

    public function removeStop(DomesticStopMultiple $stop): self
    {
        if ($this->stops->removeElement($stop)) {
            // set the owning side to null (unless already changed)
            if ($stop->getStopDay() === $this) {
                $stop->setStopDay(null);
            }
        }

        return $this;
    }

    public function getStopSummary(): ?DomesticStopSummary
    {
        return $this->stopSummary;
    }

    public function setStopSummary(DomesticStopSummary $stopSummary): self
    {
        $this->stopSummary = $stopSummary;

        // set the owning side of the relation if necessary
        if ($stopSummary->getStopDay() !== $this) {
            $stopSummary->setStopDay($this);
        }

        return $this;
    }
}
