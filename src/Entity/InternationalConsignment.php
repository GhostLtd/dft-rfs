<?php

namespace App\Entity;

use App\Repository\InternationalConsignmentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternationalConsignmentRepository::class)
 */
class InternationalConsignment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=InternationalStop::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $loadingStop;

    /**
     * @ORM\ManyToOne(targetEntity=InternationalStop::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $unloadingStop;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $goodsDescription;

    /**
     * @ORM\ManyToOne(targetEntity=HazardousGood::class)
     */
    private $hazardousGoodsType;

    /**
     * @ORM\ManyToOne(targetEntity=CargoTransportMeans::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $cargoTransportMeans;

    /**
     * @ORM\Column(type="integer")
     */
    private $weightOfGoodsCarried;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLoadingStop(): ?InternationalStop
    {
        return $this->loadingStop;
    }

    public function setLoadingStop(?InternationalStop $loadingStop): self
    {
        $this->loadingStop = $loadingStop;

        return $this;
    }

    public function getUnloadingStop(): ?InternationalStop
    {
        return $this->unloadingStop;
    }

    public function setUnloadingStop(?InternationalStop $unloadingStop): self
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

    public function getHazardousGoodsType(): ?HazardousGood
    {
        return $this->hazardousGoodsType;
    }

    public function setHazardousGoodsType(?HazardousGood $hazardousGoodsType): self
    {
        $this->hazardousGoodsType = $hazardousGoodsType;

        return $this;
    }

    public function getCargoTransportMeans(): ?CargoTransportMeans
    {
        return $this->cargoTransportMeans;
    }

    public function setCargoTransportMeans(?CargoTransportMeans $cargoTransportMeans): self
    {
        $this->cargoTransportMeans = $cargoTransportMeans;

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
