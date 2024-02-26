<?php

namespace App\Entity\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoTypeTrait;
use App\Entity\HazardousGoodsTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

trait StopTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    
    #[Assert\NotBlank(message: 'domestic.day.origin.not-blank', groups: ['origin-day', 'admin-day-stop'])]
    #[Assert\NotBlank(message: 'domestic.day-stop.origin.not-blank', groups: ['origin-day-stop'])]
    #[Assert\Length(max: 255, maxMessage: 'domestic.day.location.max-length', groups: ['origin-day', 'origin-day-stop', 'admin-day-stop'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $originLocation = null;

    #[Assert\NotBlank(message: 'domestic.day.destination.not-blank', groups: ['destination-day', 'admin-day-stop'])]
    #[Assert\NotBlank(message: 'domestic.day-stop.destination.not-blank', groups: ['destination-day-stop'])]
    #[Assert\Length(max: 255, maxMessage: 'domestic.day.location.max-length', groups: ['destination-day', 'destination-day-stop', 'admin-day-stop'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $destinationLocation = null;

    #[Assert\NotNull(message: 'domestic.day.origin.goods-loaded', groups: ['origin-day', 'origin-day-stop', 'admin-day-stop'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $goodsLoaded = null;

    #[Assert\NotNull(message: 'domestic.day.origin.goods-from', groups: ['origin-ports', 'admin-day-stop-loaded'])]
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $goodsTransferredFrom = null;

    #[Assert\NotNull(message: 'domestic.day.destination.goods-unloaded', groups: ['destination-day', 'destination-day-stop', 'admin-day-stop'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $goodsUnloaded = null;

    #[Assert\NotNull(message: 'domestic.day.destination.goods-to', groups: ['destination-ports', 'admin-day-stop-unloaded'])]
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $goodsTransferredTo = null;

    #[Assert\NotNull(message: 'domestic.day.border-crossed.not-null', groups: ['border-crossing'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $borderCrossed = null;

    
    #[Assert\Length(max: 255, maxMessage: 'domestic.day.border-crossing.max-length', groups: ['border-crossing'])]
    #[Assert\Expression('!this.getBorderCrossed() or this.getBorderCrossingLocation()', message: 'domestic.day.border-crossing.not-null', groups: ['border-crossing'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $borderCrossingLocation = null;

    #[Assert\NotNull(message: 'domestic.goods-carried.not-blank', groups: ['goods-description', 'admin-day-stop'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $goodsDescription = null;

    #[Assert\Expression("(this.getGoodsDescription() != constant('App\\\\Entity\\\\AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER')) || value != null", message: 'common.goods-description-other.not-blank', groups: ['goods-description', 'admin-day-stop'])]
    #[Assert\Length(max: 255, maxMessage: 'common.goods-description-other.max-length', groups: ['goods-description', 'admin-day-stop'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $goodsDescriptionOther = null;

    use HazardousGoodsTrait;
    use CargoTypeTrait;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOriginLocation(): ?string
    {
        return $this->originLocation;
    }

    public function setOriginLocation(?string $originLocation): self
    {
        $this->originLocation = $originLocation;
        return $this;
    }

    public function getDestinationLocation(): ?string
    {
        return $this->destinationLocation;
    }

    public function setDestinationLocation(?string $destinationLocation): self
    {
        $this->destinationLocation = $destinationLocation;
        return $this;
    }

    public function getGoodsTransferredFrom(): ?int
    {
        return $this->goodsTransferredFrom;
    }

    public function setGoodsTransferredFrom(?int $goodsTransferredFrom): self
    {
        $this->goodsTransferredFrom = $goodsTransferredFrom;
        return $this;
    }

    public function getGoodsLoaded(): ?bool
    {
        return $this->goodsLoaded;
    }

    public function setGoodsLoaded(?bool $goodsLoaded): self
    {
        if ($this->getGoodsLoaded() !== $goodsLoaded) {
            $this->goodsTransferredFrom = null;
        }
        $this->goodsLoaded = $goodsLoaded;
        return $this;
    }

    public function getGoodsLoadedIsPort(): bool
    {
        if (!$this->getGoodsLoaded()) return false;
        return ($this->goodsTransferredFrom > Day::TRANSFERRED_NONE);
    }

    public function getGoodsTransferredTo(): ?int
    {
        return $this->goodsTransferredTo;
    }

    public function setGoodsTransferredTo(?int $goodsTransferredTo): self
    {
        $this->goodsTransferredTo = $goodsTransferredTo;
        return $this;
    }

    public function getGoodsUnloaded(): ?bool
    {
        return $this->goodsUnloaded;
    }

    public function setGoodsUnloaded(?bool $goodsUnloaded): self
    {
        if ($this->getGoodsUnloaded() !== $goodsUnloaded) {
            $this->goodsTransferredTo = null;
        }
        $this->goodsUnloaded = $goodsUnloaded;
        return $this;
    }

    public function getGoodsUnloadedIsPort(): bool
    {
        if (!$this->getGoodsUnloaded()) return false;
        return $this->goodsTransferredTo > Day::TRANSFERRED_NONE;
    }

    public function getBorderCrossed(): ?bool
    {
        return $this->borderCrossed;
    }

    public function setBorderCrossed(?bool $borderCrossed): self
    {
        $this->borderCrossed = $borderCrossed;
        return $this;
    }

    public function getBorderCrossingLocation(): ?string
    {
        return $this->borderCrossingLocation;
    }

    public function setBorderCrossingLocation(?string $borderCrossingLocation): self
    {
        $this->borderCrossingLocation = $borderCrossingLocation;
        return $this;
    }

    public function getGoodsDescription(): ?string
    {
        return $this->goodsDescription;
    }

    public function setGoodsDescription(?string $goodsDescription): self
    {
        $this->goodsDescription = $goodsDescription;

        if ($goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY) {
            $this
                ->setHazardousGoodsCode(null)
                ->setCargoTypeCode(null);
        }

        return $this;
    }

    public function getGoodsDescriptionOther(): ?string
    {
        return $this->goodsDescriptionOther;
    }

    public function setGoodsDescriptionOther(?string $goodsDescriptionOther): self
    {
        $this->goodsDescriptionOther = $goodsDescriptionOther;
        return $this;
    }


    public function isGoodsDescriptionEmptyOption(): bool
    {
        return $this->goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY;
    }

    public function getGoodsDescriptionNormalized(): ?string
    {
        if ($this->goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER) {
            return $this->getGoodsDescriptionOther();
        }
        return $this->getGoodsDescription();
    }
}
