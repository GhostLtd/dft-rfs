<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait DomesticStopTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $startLocation;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $destinationLocation;

    /**
     * @ORM\Column(type="smallint")
     */
    private $transferredFrom;

    /**
     * @ORM\Column(type="smallint")
     */
    private $transferredTo;

    /**
     * @ORM\Embedded(class="App\Entity\Distance")
     */
    private $distanceTravelledLoaded;

    /**
     * @ORM\Embedded(class="App\Entity\Distance")
     */
    private $distanceTravelledUnloaded;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $borderCrossingLocation;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartLocation(): ?string
    {
        return $this->startLocation;
    }

    public function setStartLocation(string $startLocation): self
    {
        $this->startLocation = $startLocation;

        return $this;
    }

    public function getDestinationLocation(): ?string
    {
        return $this->destinationLocation;
    }

    public function setDestinationLocation(string $destinationLocation): self
    {
        $this->destinationLocation = $destinationLocation;

        return $this;
    }

    public function getTransferredFrom(): ?int
    {
        return $this->transferredFrom;
    }

    public function setTransferredFrom(int $transferredFrom): self
    {
        $this->transferredFrom = $transferredFrom;

        return $this;
    }

    public function getTransferredTo(): ?int
    {
        return $this->transferredTo;
    }

    public function setTransferredTo(int $transferredTo): self
    {
        $this->transferredTo = $transferredTo;

        return $this;
    }

    public function getDistanceTravelledLoaded(): ?Distance
    {
        return $this->distanceTravelledLoaded;
    }

    public function setDistanceTravelledLoaded(Distance $distanceTravelledLoaded): self
    {
        $this->distanceTravelledLoaded = $distanceTravelledLoaded;

        return $this;
    }

    public function getDistanceTravelledUnloaded(): ?Distance
    {
        return $this->distanceTravelledUnloaded;
    }

    public function setDistanceTravelledUnloaded(Distance $distanceTravelledUnloaded): self
    {
        $this->distanceTravelledUnloaded = $distanceTravelledUnloaded;

        return $this;
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
}
