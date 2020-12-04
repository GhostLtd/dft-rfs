<?php

namespace App\Entity\Domestic;

use App\Entity\CargoTypeTrait;
use App\Entity\HazardousGoodsTrait;
use Doctrine\ORM\Mapping as ORM;

trait StopTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $originLocation;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $destinationLocation;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $goodsTransferredFrom;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $goodsTransferredTo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $borderCrossingLocation;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $goodsDescription;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $goodsDescriptionOther;

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
        if (!is_numeric($this->goodsTransferredFrom)) return null;
        return $this->goodsTransferredFrom > Day::NOT_TRANSFERRED;
    }
    public function getGoodsLoadedIsPort(): bool
    {
        if (!is_numeric($this->goodsTransferredFrom)) return false;
        return ($this->goodsTransferredFrom > Day::TRANSFERRED_NONE);
    }

    public function setGoodsLoaded(?bool $goodsLoaded): self
    {
        if ($this->getGoodsLoaded() !== $goodsLoaded) {
            $this->goodsTransferredFrom = $goodsLoaded ? Day::TRANSFERRED : Day::NOT_TRANSFERRED;
        }
        return $this;
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
        if (!is_numeric($this->goodsTransferredTo)) return null;
        return $this->goodsTransferredTo > Day::NOT_TRANSFERRED;
    }

    public function getGoodsUnloadedIsPort(): bool
    {
        if (!is_numeric($this->goodsTransferredTo)) return false;
        return $this->goodsTransferredTo > Day::TRANSFERRED_NONE;
    }

    public function setGoodsUnloaded(?bool $goodsUnloaded): self
    {
        if ($this->getGoodsUnloaded() !== $goodsUnloaded) {
            $this->goodsTransferredTo = $goodsUnloaded ? Day::TRANSFERRED : Day::NOT_TRANSFERRED;
        }
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

        if ($goodsDescription === Day::GOODS_DESCRIPTION_EMPTY) {
            $this
                ->setHazardousGoodsCode(null)
                ->setCargoTypeCode(null)
            ;
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


    public function isGoodsDescriptionEmptyOption()
    {
        return $this->goodsDescription === Day::GOODS_DESCRIPTION_EMPTY;
    }
}
