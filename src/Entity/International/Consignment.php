<?php

namespace App\Entity\International;

use App\Entity\CargoTransportMeans;
use App\Entity\HazardousGood;
use App\Repository\International\ConsignmentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConsignmentRepository::class)
 * @ORM\Table(name="international_consignment")
 */
class Consignment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Stop::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $loadingStop;

    /**
     * @ORM\ManyToOne(targetEntity=Stop::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $unloadingStop;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $goodsDescription;

    use HazardousGoodsTrait;
    use CargoTypeTrait;

    /**
     * @ORM\Column(type="integer")
     */
    private $weightOfGoodsCarried;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLoadingStop(): ?Stop
    {
        return $this->loadingStop;
    }

    public function setLoadingStop(?Stop $loadingStop): self
    {
        $this->loadingStop = $loadingStop;

        return $this;
    }

    public function getUnloadingStop(): ?Stop
    {
        return $this->unloadingStop;
    }

    public function setUnloadingStop(?Stop $unloadingStop): self
    {
        $this->unloadingStop = $unloadingStop;

        return $this;
    }

    public function getGoodsDescription(): ?string
    {
        return $this->goodsDescription;
    }

    public function setGoodsDescription(string $goodsDescription): self
    {
        $this->goodsDescription = $goodsDescription;

        return $this;
    }

    public function getWeightOfGoodsCarried(): ?int
    {
        return $this->weightOfGoodsCarried;
    }

    public function setWeightOfGoodsCarried(int $weightOfGoodsCarried): self
    {
        $this->weightOfGoodsCarried = $weightOfGoodsCarried;

        return $this;
    }
}
