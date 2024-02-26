<?php

namespace App\Entity;

// This should be kept in sync with HazardousGoodsTrait
interface HazardousGoodsInterface
{
    public function getHazardousGoodsCode(): ?string;
    public function setHazardousGoodsCode(?string $hazardousGoodsCode): self;
}
