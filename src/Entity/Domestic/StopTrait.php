<?php

namespace App\Entity\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoTypeTrait;
use App\Entity\HazardousGoodsTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @Assert\NotBlank(message="domestic.day.location.not-blank", groups={"origin"})
     * @Assert\Length(max=255, maxMessage="domestic.day.location.max-length", groups={"origin"})
     */
    private $originLocation;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="domestic.day.location.not-blank", groups={"destination"})
     * @Assert\Length(max=255, maxMessage="domestic.day.location.max-length", groups={"destination"})
     */
    private $destinationLocation;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(message="common.choice.not-null", groups={"origin"})
     */
    private $goodsLoaded;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     * @Assert\NotNull(message="common.choice.not-null", groups={"origin-ports"})
     */
    private $goodsTransferredFrom;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(message="common.choice.not-null", groups={"destination"})
     */
    private $goodsUnloaded;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     * @Assert\NotNull(message="common.choice.not-null", groups={"destination-ports"})
     */
    private $goodsTransferredTo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="domestic.day.border-crossing.not-blank", groups={"border-crossing"})
     * @Assert\Length(max=255, maxMessage="domestic.day.border-crossing.max-length", groups={"border-crossing"})
     */
    private $borderCrossingLocation;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull(message="common.choice.not-null", groups={"goods-description"})
     */
    private $goodsDescription;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Expression("(this.getGoodsDescription() != constant('App\\Entity\\AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER')) || value != null", message="common.goods-description-other.not-blank", groups={"goods-description"})
     * @Assert\Length(max=255, maxMessage="common.goods-description-other.max-length", groups={"goods-description"})
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
        return $this->goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY;
    }
}
