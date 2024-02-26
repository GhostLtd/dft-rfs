<?php

namespace App\Entity\Domestic;

use App\Entity\CargoTypeInterface;
use App\Entity\HazardousGoodsInterface;

// This should be kept in sync with StopTrait
interface StopInterface extends CargoTypeInterface, HazardousGoodsInterface
{
    public function getId(): ?string;
    public function getOriginLocation(): ?string;
    public function setOriginLocation(?string $originLocation): self;
    public function getDestinationLocation(): ?string;
    public function setDestinationLocation(?string $destinationLocation): self;
    public function getGoodsTransferredFrom(): ?int;
    public function setGoodsTransferredFrom(?int $goodsTransferredFrom): self;
    public function getGoodsLoaded(): ?bool;
    public function setGoodsLoaded(?bool $goodsLoaded): self;
    public function getGoodsLoadedIsPort(): bool;
    public function getGoodsTransferredTo(): ?int;
    public function setGoodsTransferredTo(?int $goodsTransferredTo): self;
    public function getGoodsUnloaded(): ?bool;
    public function setGoodsUnloaded(?bool $goodsUnloaded): self;
    public function getGoodsUnloadedIsPort(): bool;
    public function getBorderCrossed(): ?bool;
    public function setBorderCrossed(?bool $borderCrossed): self;
    public function getBorderCrossingLocation(): ?string;
    public function setBorderCrossingLocation(?string $borderCrossingLocation): self;
    public function getGoodsDescription(): ?string;
    public function setGoodsDescription(?string $goodsDescription): self;
    public function getGoodsDescriptionOther(): ?string;
    public function setGoodsDescriptionOther(?string $goodsDescriptionOther): self;
    public function isGoodsDescriptionEmptyOption(): bool;
    public function getGoodsDescriptionNormalized(): ?string;
}
