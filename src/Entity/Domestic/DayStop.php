<?php

namespace App\Entity\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\Distance;
use App\Entity\GoodsDescriptionInterface;
use App\Entity\HazardousGoodsInterface;
use App\Form\Validator as AppAssert;
use App\Repository\Domestic\DayStopRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table('domestic_day_stop')]
#[ORM\Entity(repositoryClass: DayStopRepository::class)]
class DayStop implements BorderCrossingInterface, GoodsDescriptionInterface, HazardousGoodsInterface, StopInterface
{
    use StopTrait {
        setGoodsDescription as traitSetGoodsDescription;
    }

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $number = null;

    #[Assert\NotNull(message: 'domestic.day-stop.goods-weight.not-null', groups: ['goods-weight', 'admin-day-stop-not-empty'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive', groups: ['goods-weight', 'admin-day-stop-not-empty'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['goods-weight', 'admin-day-stop-not-empty'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $weightOfGoodsCarried = null;

    #[Assert\NotNull(message: 'domestic.day-stop.was-at-capacity.not-null', groups: ['at-capacity', 'admin-day-stop-not-empty'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $wasAtCapacity = null;

    /**
     * !! unused - needed for validation attachment
     */
    #[Assert\Expression('!this.getWasAtCapacity() or (this.getWasLimitedBySpace() or this.getWasLimitedByWeight())', message: 'domestic.day-stop.was-at-capacity.invalid', groups: ['at-capacity', 'admin-day-stop-not-empty'])]
    private $wasLimitedBy;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $wasLimitedByWeight = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $wasLimitedBySpace = null;

    #[AppAssert\ValidValueUnit(groups: ["day-stop.distance-travelled", "admin-day-stop"])]
    #[ORM\Embedded(class: Distance::class)]
    private $distanceTravelled;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: Day::class, inversedBy: 'stops')]
    private ?Day $day = null;

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

    public function getWasAtCapacity(): ?bool
    {
        return $this->wasAtCapacity;
    }

    public function setWasAtCapacity(?bool $wasAtCapacity): self
    {
        if (!$wasAtCapacity) {
            $this->setWasLimitedBy([]);
        }
        $this->wasAtCapacity = $wasAtCapacity;
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

    public function setWasLimitedBy(array $limitedBy = []): self
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

    #[\Override]
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
    public function transitionGoodsNotUnloadedNICallback(): bool
    {
        return
            $this->getDay()->getResponse()->getSurvey()->getIsNorthernIreland()
            && !$this->getGoodsUnloaded();
    }

    public function transitionGoodsNotUnloadedGBCallback(): bool
    {
        return
            !$this->getDay()->getResponse()->getSurvey()->getIsNorthernIreland()
            && !$this->getGoodsUnloaded();
    }

    public function isNorthernIrelandSurvey(): ?bool
    {
        return $this->getDay()->getResponse()->getSurvey()->getIsNorthernIreland();
    }

    public function merge(DayStop $dayStopToMerge): void
    {
        $this->setOriginLocation($dayStopToMerge->getOriginLocation());
        $this->setGoodsLoaded($dayStopToMerge->getGoodsLoaded());
        $this->setGoodsTransferredFrom($dayStopToMerge->getGoodsTransferredFrom());
        $this->setDestinationLocation($dayStopToMerge->getDestinationLocation());
        $this->setGoodsUnloaded($dayStopToMerge->getGoodsUnloaded());
        $this->setGoodsTransferredTo($dayStopToMerge->getGoodsTransferredTo());
        $this->setBorderCrossed($dayStopToMerge->getBorderCrossed());
        $this->setBorderCrossingLocation($dayStopToMerge->getBorderCrossingLocation());
        $this->setDistanceTravelled($dayStopToMerge->getDistanceTravelled());
        $this->setGoodsDescription($dayStopToMerge->getGoodsDescription());
        $this->setGoodsDescriptionOther($dayStopToMerge->getGoodsDescriptionOther());
        $this->setHazardousGoodsCode($dayStopToMerge->getHazardousGoodsCode());
        $this->setCargoTypeCode($dayStopToMerge->getCargoTypeCode());
        $this->setWeightOfGoodsCarried($dayStopToMerge->getWeightOfGoodsCarried());
        $this->setWasAtCapacity($dayStopToMerge->getWasAtCapacity());
        $this->setWasLimitedBy($dayStopToMerge->getWasLimitedBy());
    }
}
