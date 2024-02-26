<?php

namespace App\Entity\Domestic;

use App\Entity\Distance;
use App\Entity\GoodsDescriptionInterface;
use App\Entity\HazardousGoodsInterface;
use App\Repository\Domestic\DaySummaryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\Validator as AppAssert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table('domestic_day_summary')]
#[ORM\Entity(repositoryClass: DaySummaryRepository::class)]
class DaySummary implements BorderCrossingInterface, GoodsDescriptionInterface, HazardousGoodsInterface, StopInterface
{
    use StopTrait;

    #[Assert\NotBlank(message: 'domestic.day.furthest-point.not-blank', groups: ['day-summary.furthest-stop', 'admin-day-summary'])]
    #[Assert\Length(max: 255, maxMessage: 'domestic.day.location.max-length', groups: ['day-summary.furthest-stop', 'admin-day-summary'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $furthestStop = null;

    #[Assert\NotNull(message: 'domestic.day.weight-loaded.not-null', groups: ['day-summary.goods-weight', 'admin-day-summary'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive', groups: ['day-summary.goods-weight', 'admin-day-summary'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['day-summary.goods-weight', 'admin-day-summary'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $weightOfGoodsLoaded = null;

    #[Assert\NotNull(message: 'domestic.day.weight-unloaded.not-null', groups: ['day-summary.goods-weight', 'admin-day-summary'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive', groups: ['day-summary.goods-weight', 'admin-day-summary'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['day-summary.goods-weight', 'admin-day-summary'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $weightOfGoodsUnloaded = null;

    #[Assert\NotNull(message: 'domestic.day.number-of-stops.loading.not-null', groups: ['day-summary.number-of-stops', 'admin-day-summary'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive', groups: ['day-summary.number-of-stops', 'admin-day-summary'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['day-summary.number-of-stops', 'admin-day-summary'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $numberOfStopsLoading = null;

    #[Assert\NotNull(message: 'domestic.day.number-of-stops.unloading.not-null', groups: ['day-summary.number-of-stops', 'admin-day-summary'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive', groups: ['day-summary.number-of-stops', 'admin-day-summary'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['day-summary.number-of-stops', 'admin-day-summary'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $numberOfStopsUnloading = null;

    #[Assert\NotNull(message: 'domestic.day.number-of-stops.loading-and-unloading.not-null', groups: ['day-summary.number-of-stops', 'admin-day-summary'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive', groups: ['day-summary.number-of-stops', 'admin-day-summary'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['day-summary.number-of-stops', 'admin-day-summary'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $numberOfStopsLoadingAndUnloading = null;

    #[AppAssert\ValidValueUnit(groups: ["day-summary.distance-travelled", "admin-day-summary"])]
    #[ORM\Embedded(class: Distance::class)]
    private $distanceTravelledLoaded;

    #[AppAssert\ValidValueUnit(groups: ["day-summary.distance-travelled", "admin-day-summary"])]
    #[ORM\Embedded(class: Distance::class)]
    private $distanceTravelledUnloaded;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\OneToOne(inversedBy: 'summary', targetEntity: Day::class, cascade: ['persist', 'remove'])]
    private ?Day $day = null;

    #[Assert\Callback(groups: ['day-summary.number-of-stops.total-stops', 'admin-day-summary.total-stops'])]
    public function validateNumberOfStops(ExecutionContextInterface $context): void
    {
        $totalStops =
            $this->getNumberOfStopsLoading() +
            $this->getNumberOfStopsUnloading() +
            $this->getNumberOfStopsLoadingAndUnloading();

        if ($totalStops < 5) {
            $context
                ->buildViolation("domestic.day.number-of-stops.at-least-five")
                ->atPath('number-of-stops')
                ->addViolation();
        }
    }

    public function isNorthernIrelandSurvey(): ?bool
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

    public function merge(DaySummary $daySummaryToMerge): void
    {
        $this->setOriginLocation($daySummaryToMerge->getOriginLocation());
        $this->setGoodsLoaded($daySummaryToMerge->getGoodsLoaded());
        $this->setGoodsTransferredFrom($daySummaryToMerge->getGoodsTransferredFrom());
        $this->setDestinationLocation($daySummaryToMerge->getDestinationLocation());
        $this->setGoodsUnloaded($daySummaryToMerge->getGoodsUnloaded());
        $this->setGoodsTransferredTo($daySummaryToMerge->getGoodsTransferredTo());
        $this->setBorderCrossed($daySummaryToMerge->getBorderCrossed());
        $this->setBorderCrossingLocation($daySummaryToMerge->getBorderCrossingLocation());
        $this->setFurthestStop($daySummaryToMerge->getFurthestStop());
        $this->setDistanceTravelledLoaded($daySummaryToMerge->getDistanceTravelledLoaded());
        $this->setDistanceTravelledUnloaded($daySummaryToMerge->getDistanceTravelledUnloaded());
        $this->setGoodsDescription($daySummaryToMerge->getGoodsDescription());
        $this->setGoodsDescriptionOther($daySummaryToMerge->getGoodsDescriptionOther());
        $this->setHazardousGoodsCode($daySummaryToMerge->getHazardousGoodsCode());
        $this->setCargoTypeCode($daySummaryToMerge->getCargoTypeCode());
        $this->setWeightOfGoodsLoaded($daySummaryToMerge->getWeightOfGoodsLoaded());
        $this->setWeightOfGoodsUnloaded($daySummaryToMerge->getWeightOfGoodsUnloaded());
        $this->setNumberOfStopsLoading($daySummaryToMerge->getNumberOfStopsLoading());
        $this->setNumberOfStopsUnloading($daySummaryToMerge->getNumberOfStopsUnloading());
        $this->setNumberOfStopsLoadingAndUnloading($daySummaryToMerge->getNumberOfStopsLoadingAndUnloading());
    }
}
