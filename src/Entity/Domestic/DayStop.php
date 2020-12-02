<?php

namespace App\Entity\Domestic;

use App\Entity\Distance;
use App\Repository\Domestic\DayStopRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DayStopRepository::class)
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
     * @ORM\Embedded(class=Distance::class)
     */
    private $distanceTravelled;

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

    public function getWasLimitedBy(): ?array
    {
        $limitedBy = [];
        if ($this->getWasLimitedBySpace()) $limitedBy[] = 'space';
        if ($this->getWasLimitedByWeight()) $limitedBy[] = 'weight';
        return $limitedBy;
    }

    public function setWasLimitedBy(?array $limitedBy): self
    {
        $this->setWasLimitedBySpace(in_array('space', $limitedBy));
        $this->setWasLimitedByWeight(in_array('weight', $limitedBy));
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

    public function getDistanceTravelled(): ?Distance
    {
        return $this->distanceTravelled;
    }

    public function setDistanceTravelled(?Distance $distanceTravelledLoaded): self
    {
        $this->distanceTravelled = $distanceTravelledLoaded;

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
