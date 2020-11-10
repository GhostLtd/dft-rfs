<?php

namespace App\Entity;

use App\Repository\DomesticStopMultipleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DomesticStopMultipleRepository::class)
 */
class DomesticStopMultiple
{
    use DomesticStopTrait;

    /**
     * @ORM\Column(type="integer")
     */
    private $weightOfGoodsCarried;

    /**
     * @ORM\Column(type="boolean")
     */
    private $wasLimitedByWeight;

    /**
     * @ORM\Column(type="boolean")
     */
    private $wasLimitedBySpace;

    /**
     * @ORM\ManyToOne(targetEntity=DomesticStopDay::class, inversedBy="stops")
     * @ORM\JoinColumn(nullable=false)
     */
    private $stopDay;

    public function getWeightOfGoodsCarried(): ?int
    {
        return $this->weightOfGoodsCarried;
    }

    public function setWeightOfGoodsCarried(int $weightOfGoodsCarried): self
    {
        $this->weightOfGoodsCarried = $weightOfGoodsCarried;

        return $this;
    }

    public function getWasLimitedByWeight(): ?bool
    {
        return $this->wasLimitedByWeight;
    }

    public function setWasLimitedByWeight(bool $wasLimitedByWeight): self
    {
        $this->wasLimitedByWeight = $wasLimitedByWeight;

        return $this;
    }

    public function getWasLimitedBySpace(): ?bool
    {
        return $this->wasLimitedBySpace;
    }

    public function setWasLimitedBySpace(bool $wasLimitedBySpace): self
    {
        $this->wasLimitedBySpace = $wasLimitedBySpace;

        return $this;
    }

    public function getStopDay(): ?DomesticStopDay
    {
        return $this->stopDay;
    }

    public function setStopDay(?DomesticStopDay $stopDay): self
    {
        $this->stopDay = $stopDay;

        return $this;
    }
}
