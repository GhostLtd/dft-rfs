<?php

namespace App\Entity;

use App\Repository\DomesticStopSummaryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DomesticStopSummaryRepository::class)
 */
class DomesticStopSummary
{
    use DomesticStopTrait;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $furthestStop;

    /**
     * @ORM\Column(type="integer")
     */
    private $weightOfGoodsLoaded;

    /**
     * @ORM\Column(type="integer")
     */
    private $weightOfGoodsUnloaded;

    /**
     * @ORM\Column(type="integer")
     */
    private $numberOfStopsLoading;

    /**
     * @ORM\Column(type="integer")
     */
    private $numberOfStopsUnloading;

    /**
     * @ORM\Column(type="integer")
     */
    private $numberOfStopsLoadingAndUnloading;

    /**
     * @ORM\OneToOne(targetEntity=DomesticStopDay::class, inversedBy="stopSummary", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $stopDay;

    public function getFurthestStop(): ?string
    {
        return $this->furthestStop;
    }

    public function setFurthestStop(string $furthestStop): self
    {
        $this->furthestStop = $furthestStop;

        return $this;
    }

    public function getWeightOfGoodsLoaded(): ?int
    {
        return $this->weightOfGoodsLoaded;
    }

    public function setWeightOfGoodsLoaded(int $weightOfGoodsLoaded): self
    {
        $this->weightOfGoodsLoaded = $weightOfGoodsLoaded;

        return $this;
    }

    public function getWeightOfGoodsUnloaded(): ?int
    {
        return $this->weightOfGoodsUnloaded;
    }

    public function setWeightOfGoodsUnloaded(int $weightOfGoodsUnloaded): self
    {
        $this->weightOfGoodsUnloaded = $weightOfGoodsUnloaded;

        return $this;
    }

    public function getNumberOfStopsLoading(): ?int
    {
        return $this->numberOfStopsLoading;
    }

    public function setNumberOfStopsLoading(int $numberOfStopsLoading): self
    {
        $this->numberOfStopsLoading = $numberOfStopsLoading;

        return $this;
    }

    public function getNumberOfStopsUnloading(): ?int
    {
        return $this->numberOfStopsUnloading;
    }

    public function setNumberOfStopsUnloading(int $numberOfStopsUnloading): self
    {
        $this->numberOfStopsUnloading = $numberOfStopsUnloading;

        return $this;
    }

    public function getNumberOfStopsLoadingAndUnloading(): ?int
    {
        return $this->numberOfStopsLoadingAndUnloading;
    }

    public function setNumberOfStopsLoadingAndUnloading(int $numberOfStopsLoadingAndUnloading): self
    {
        $this->numberOfStopsLoadingAndUnloading = $numberOfStopsLoadingAndUnloading;

        return $this;
    }

    public function getStopDay(): ?DomesticStopDay
    {
        return $this->stopDay;
    }

    public function setStopDay(DomesticStopDay $stopDay): self
    {
        $this->stopDay = $stopDay;

        return $this;
    }
}
