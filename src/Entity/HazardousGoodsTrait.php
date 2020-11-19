<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait HazardousGoodsTrait
{
    /**
     * @ORM\Column(type="string", length=5)
     */
    private $hazardousGoodsCode;

    public function getHazardousGoodsCode(): ?string
    {
        return $this->hazardousGoodsCode;
    }

    public function setHazardousGoodsCode(?string $hazardousGoodsCode): self
    {
        $this->hazardousGoodsCode = $hazardousGoodsCode;

        return $this;
    }
}
