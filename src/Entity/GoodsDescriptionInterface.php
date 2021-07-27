<?php


namespace App\Entity;


interface GoodsDescriptionInterface
{
    public function getGoodsDescription(): ?string;
    public function getGoodsDescriptionOther(): ?string;
}