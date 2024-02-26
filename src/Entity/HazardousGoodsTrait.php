<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait HazardousGoodsTrait
{
    #[Assert\NotNull(message: 'common.choice.not-null', groups: ['admin_action_load', 'admin-day-stop-not-empty'])]
    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    private ?string $hazardousGoodsCode = null;

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
