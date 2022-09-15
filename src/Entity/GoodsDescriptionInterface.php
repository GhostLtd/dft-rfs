<?php

namespace App\Entity;

interface GoodsDescriptionInterface
{
    public function getGoodsDescription(): ?string;
    public function getGoodsDescriptionOther(): ?string;
    public function setGoodsDescription(?string $goodsDescription): self;
    public function setGoodsDescriptionOther(?string $goodsDescriptionOther): self;
}