<?php

namespace App\Entity\Domestic;

use App\Entity\CargoTypeTrait;
use App\Entity\Distance;
use App\Entity\HazardousGoodsTrait;
use Doctrine\ORM\Mapping as ORM;

trait StopTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
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
     * @ORM\Embedded(class=Distance::class)
     */
    private $distanceTravelledLoaded;

    /**
     * @ORM\Embedded(class=Distance::class)
     */
    private $distanceTravelledUnloaded;

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

    public function getId(): ?int
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
        return $this->goodsTransferredFrom > 0;
    }

    public function setGoodsLoaded(?bool $goodsLoaded): self
    {
        if ($this->getGoodsLoaded() != $goodsLoaded) {
            $this->goodsTransferredFrom = $goodsLoaded ? Day::TRANSFERRED : null;
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
        return $this->goodsTransferredTo > 0;
    }

    public function setGoodsUnloaded(?bool $goodsUnloaded): self
    {
        if ($this->getGoodsUnloaded() != $goodsUnloaded) {
            $this->goodsTransferredTo = $goodsUnloaded ? Day::TRANSFERRED : null;
        }
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
}
