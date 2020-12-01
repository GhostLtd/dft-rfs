<?php

namespace App\Entity\Domestic;

use App\Repository\Domestic\StopMultipleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StopMultipleRepository::class)
 * @ORM\Table("domestic_day_stop")
 */
class DayStop
{
    use StopTrait;

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
     * @ORM\ManyToOne(targetEntity=Day::class, inversedBy="stops")
     * @ORM\JoinColumn(nullable=false)
     */
    private $day;

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

    public function getDay(): ?Day
    {
        return $this->day;
    }

    public function setDay(?Day $day): self
    {
        $this->day = $day;

        return $this;
    }


    // transition callbacks
    public function transitionGoodsNotUnloadedNICallback()
    {
        return
            $this->getDay()->getResponse()->getSurvey()->getIsNorthernIreland()
            && !$this->getGoodsUnloaded()
            ;
    }

    public function transitionGoodsNotUnloadedGBCallback()
    {
        return
            !$this->getDay()->getResponse()->getSurvey()->getIsNorthernIreland()
            && !$this->getGoodsUnloaded()
            ;
    }

    public function isNorthernIrelandSurvey()
    {
        return $this->getDay()->getResponse()->getSurvey()->getIsNorthernIreland();
    }

}
