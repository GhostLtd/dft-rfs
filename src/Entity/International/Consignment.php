<?php

namespace App\Entity\International;

use App\Entity\CargoTypeTrait;
use App\Entity\HazardousGoodsTrait;
use App\Repository\International\ConsignmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ConsignmentRepository::class)
 * @ORM\Table(name="international_consignment")
 */
class Consignment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Trip::class, inversedBy="stops")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trip;

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

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Expression("(this.getGoodsDescription() != constant('App\\Entity\\AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER')) || value != null", message="domestic.day.goods-description-other.not-blank", groups={"goods-description"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"goods-description"})
     */
    private $goodsDescriptionOther;

    use HazardousGoodsTrait;
    use CargoTypeTrait;

    /**
     * @ORM\Column(type="integer")
     */
    private $weightOfGoodsCarried;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): self
    {
        $this->trip = $trip;

        return $this;
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

    public function setGoodsDescription(?string $goodsDescription): self
    {
        $this->goodsDescription = $goodsDescription;

        return $this;
    }

    public function getWeightOfGoodsCarried(): ?int
    {
        return $this->weightOfGoodsCarried;
    }

    public function setWeightOfGoodsCarried(?int $weightOfGoodsCarried): self
    {
        $this->weightOfGoodsCarried = $weightOfGoodsCarried;

        return $this;
    }

    public function getGoodsDescriptionOther(): ?string
    {
        return $this->goodsDescriptionOther;
    }

    public function setGoodsDescriptionOther(?string $goodsDescriptionOther): self
    {
        $this->goodsDescriptionOther = $goodsDescriptionOther;

        return $this;
    }


    public function mergeChanges(Consignment $consignment)
    {
        $this->setWeightOfGoodsCarried($consignment->getWeightOfGoodsCarried());
        $this->setGoodsDescription($consignment->getGoodsDescription());
        $this->setCargoTypeCode($consignment->getCargoTypeCode());
        $this->setHazardousGoodsCode($consignment->getHazardousGoodsCode());
        $this->setLoadingStop($consignment->getLoadingStop());
        $this->setUnloadingStop($consignment->getUnloadingStop());
    }
}
