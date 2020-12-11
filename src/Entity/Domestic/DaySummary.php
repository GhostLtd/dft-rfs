<?php

namespace App\Entity\Domestic;

use App\Entity\Distance;
use App\Repository\Domestic\DaySummaryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\Validator as AppAssert;

/**
 * @ORM\Entity(repositoryClass=DaySummaryRepository::class)
 * @ORM\Table("domestic_day_summary")
 */
class DaySummary
{
    use StopTrait;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="domestic.day.location.not-blank", groups={"day-summary.furthest-stop"})
     */
    private $furthestStop;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotNull(message="common.number.not-null", groups={"day-summary.goods-weight"})
     * @Assert\PositiveOrZero(message="common.number.positive-or-zero", groups={"day-summary.goods-weight"})
     */
    private $weightOfGoodsLoaded;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotNull(message="common.number.not-null", groups={"day-summary.goods-weight"})
     * @Assert\PositiveOrZero(message="common.number.positive-or-zero", groups={"day-summary.goods-weight"})
     */
    private $weightOfGoodsUnloaded;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotNull(message="common.number.not-null", groups={"day-summary.number-of-stops"})
     * @Assert\PositiveOrZero(message="common.number.positive-or-zero", groups={"day-summary.number-of-stops"})
     */
    private $numberOfStopsLoading;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotNull(message="common.number.not-null", groups={"day-summary.number-of-stops"})
     * @Assert\PositiveOrZero(message="common.number.positive-or-zero", groups={"day-summary.number-of-stops"})
     */
    private $numberOfStopsUnloading;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotNull(message="common.number.not-null", groups={"day-summary.number-of-stops"})
     * @Assert\PositiveOrZero(message="common.number.positive-or-zero", groups={"day-summary.number-of-stops"})
     */
    private $numberOfStopsLoadingAndUnloading;

    /**
     * @ORM\Embedded(class=Distance::class)
     * @AppAssert\ValidValueUnit(groups={"day-summary.distance-travelled"})
     */
    private $distanceTravelledLoaded;

    /**
     * @ORM\Embedded(class=Distance::class)
     * @AppAssert\ValidValueUnit(groups={"day-summary.distance-travelled"})
     */
    private $distanceTravelledUnloaded;

    /**
     * @ORM\OneToOne(targetEntity=Day::class, inversedBy="summary", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $day;

    public function isNorthernIrelandSurvey()
    {
        return $this->getDay()->getResponse()->getSurvey()->getIsNorthernIreland();
    }

    public function getFurthestStop(): ?string
    {
        return $this->furthestStop;
    }

    public function setFurthestStop(?string $furthestStop): self
    {
        $this->furthestStop = $furthestStop;

        return $this;
    }

    public function getWeightOfGoodsLoaded(): ?int
    {
        return $this->weightOfGoodsLoaded;
    }

    public function setWeightOfGoodsLoaded(?int $weightOfGoodsLoaded): self
    {
        $this->weightOfGoodsLoaded = $weightOfGoodsLoaded;

        return $this;
    }

    public function getWeightOfGoodsUnloaded(): ?int
    {
        return $this->weightOfGoodsUnloaded;
    }

    public function setWeightOfGoodsUnloaded(?int $weightOfGoodsUnloaded): self
    {
        $this->weightOfGoodsUnloaded = $weightOfGoodsUnloaded;

        return $this;
    }

    public function getNumberOfStopsLoading(): ?int
    {
        return $this->numberOfStopsLoading;
    }

    public function setNumberOfStopsLoading(?int $numberOfStopsLoading): self
    {
        $this->numberOfStopsLoading = $numberOfStopsLoading;

        return $this;
    }

    public function getNumberOfStopsUnloading(): ?int
    {
        return $this->numberOfStopsUnloading;
    }

    public function setNumberOfStopsUnloading(?int $numberOfStopsUnloading): self
    {
        $this->numberOfStopsUnloading = $numberOfStopsUnloading;

        return $this;
    }

    public function getNumberOfStopsLoadingAndUnloading(): ?int
    {
        return $this->numberOfStopsLoadingAndUnloading;
    }

    public function setNumberOfStopsLoadingAndUnloading(?int $numberOfStopsLoadingAndUnloading): self
    {
        $this->numberOfStopsLoadingAndUnloading = $numberOfStopsLoadingAndUnloading;

        return $this;
    }

    public function getDistanceTravelledLoaded(): ?Distance
    {
        return $this->distanceTravelledLoaded;
    }

    public function setDistanceTravelledLoaded(?Distance $distanceTravelledLoaded): self
    {
        $this->distanceTravelledLoaded = $distanceTravelledLoaded;

        return $this;
    }

    public function getDistanceTravelledUnloaded(): ?Distance
    {
        return $this->distanceTravelledUnloaded;
    }

    public function setDistanceTravelledUnloaded(?Distance $distanceTravelledUnloaded): self
    {
        $this->distanceTravelledUnloaded = $distanceTravelledUnloaded;

        return $this;
    }
    public function getDay(): ?Day
    {
        return $this->day;
    }

    public function setDay(Day $day): self
    {
        $this->day = $day;

        return $this;
    }
}
