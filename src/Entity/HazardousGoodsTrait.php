<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait HazardousGoodsTrait
{
    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\NotNull(message="common.choice.not-null", groups={"admin_action_load", "admin-day-stop-not-empty"})
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
