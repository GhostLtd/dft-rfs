<?php

namespace App\Entity\Domestic;

use App\Repository\Domestic\StopDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StopDayRepository::class)
 * @ORM\Table("domestic_day")
 */
class Day
{
    const TRANSFERRED = 1;
    const TRANSFERRED_NONE = 2;
    const TRANSFERRED_PORT = 4;
    const TRANSFERRED_RAIL = 8;
    const TRANSFERRED_AIR = 16;

    const TRANSFER_TRANSLATION_PREFIX = 'domestic.transferred-port.options.';
    const TRANSFER_CHOICES = [
        self::TRANSFER_TRANSLATION_PREFIX . self::TRANSFERRED_PORT => self::TRANSFERRED_PORT,
        self::TRANSFER_TRANSLATION_PREFIX . self::TRANSFERRED_RAIL => self::TRANSFERRED_RAIL,
        self::TRANSFER_TRANSLATION_PREFIX . self::TRANSFERRED_AIR => self::TRANSFERRED_AIR,
        self::TRANSFER_TRANSLATION_PREFIX . self::TRANSFERRED_NONE => self::TRANSFERRED_NONE,
    ];

    const GOODS_DESCRIPTION_EMPTY_CONTAINER = 'empty-container';
    const GOODS_DESCRIPTION_PACKAGING = 'packaging';
    const GOODS_DESCRIPTION_GROUPAGE = 'groupage';
    const GOODS_DESCRIPTION_EMPTY = 'empty';
    const GOODS_DESCRIPTION_OTHER = 'other-goods';

    const GOODS_DESCRIPTION_TRANSLATION_PREFIX = 'domestic.goods-description.options.';
    const GOODS_DESCRIPTION_CHOICES = [
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_EMPTY_CONTAINER => self::GOODS_DESCRIPTION_EMPTY_CONTAINER,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_PACKAGING => self::GOODS_DESCRIPTION_PACKAGING,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_GROUPAGE => self::GOODS_DESCRIPTION_GROUPAGE,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_EMPTY => self::GOODS_DESCRIPTION_EMPTY,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_OTHER => self::GOODS_DESCRIPTION_OTHER,
    ];


    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=SurveyResponse::class, inversedBy="days")
     * @ORM\JoinColumn(nullable=false)
     */
    private $response;

    /**
     * @ORM\Column(type="smallint")
     */
    private $number;

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

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

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
