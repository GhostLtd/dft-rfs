<?php

namespace App\Entity\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\Distance;
use App\Form\Validator as AppAssert;
use App\Repository\Domestic\DayStopRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=DayStopRepository::class)
 * @ORM\Table("domestic_day_stop")
 */
class DayStop
{
    use StopTrait {
        setGoodsDescription as traitSetGoodsDescription;
    }

    /**
     * @ORM\Column(type="smallint")
     */
    private $number;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotNull(message="common.number.not-null", groups={"day-stop.goods-weight"})
     * @Assert\Positive(message="common.number.positive", groups={"day-stop.goods-weight"})
     */
    private $weightOfGoodsCarried;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $wasLimitedByWeight;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $wasLimitedBySpace;

    /**
     * @ORM\Embedded(class=Distance::class)
     * @AppAssert\ValidValueUnit(groups={"day-stop.distance-travelled"})
     */
    private $distanceTravelled;

    /**
     * @ORM\ManyToOne(targetEntity=Day::class, inversedBy="stops")
     * @ORM\JoinColumn(nullable=false)
     */
    private $day;

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getWeightOfGoodsCarried(): ?int
    {
        return $this->weightOfGoodsCarried;
    }

    public function setWeightOfGoodsCarried(?int $weightOfGoodsCarried): self
    {
        $this->weightOfGoodsCarried = $weightOfGoodsCarried;

        return $this;
    }

    public function getWasLimitedByWeight(): ?bool
    {
        return $this->wasLimitedByWeight;
    }

    public function setWasLimitedByWeight(?bool $wasLimitedByWeight): self
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

    public function setWasLimitedBySpace(?bool $wasLimitedBySpace): self
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

    public function setGoodsDescription(?string $goodsDescription): self
    {
        if ($goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY) {
            $this
                ->setWasLimitedBySpace(null)
                ->setWasLimitedByWeight(null)
                ->setWeightOfGoodsCarried(null)
            ;
        }
        return $this->traitSetGoodsDescription($goodsDescription);
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
