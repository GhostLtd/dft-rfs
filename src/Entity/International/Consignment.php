<?php

namespace App\Entity\International;

use App\Entity\BlameLoggable;
use App\Entity\CargoTypeTrait;
use App\Entity\GoodsDescriptionInterface;
use App\Entity\HazardousGoodsTrait;
use App\Repository\International\ConsignmentRepository;
use App\Repository\International\StopRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ConsignmentRepository::class)
 * @ORM\Table(name="international_consignment")
 */
class Consignment implements GoodsDescriptionInterface, BlameLoggable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Stop::class)
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull(message="common.choice.not-null", groups={"loading-stop"})
     */
    private $loadingStop;

    /**
     * @ORM\ManyToOne(targetEntity=Stop::class)
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull(message="common.choice.not-null", groups={"unloading-stop"})
     */
    private $unloadingStop;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull(message="common.choice.invalid", groups={"goods-description"})
     */
    private $goodsDescription;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Expression("(this.getGoodsDescription() != constant('App\\Entity\\AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER')) || value != null", message="common.goods-description-other.not-blank", groups={"goods-description"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"goods-description"})
     */
    private $goodsDescriptionOther;

    use HazardousGoodsTrait;
    use CargoTypeTrait;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(message="common.number.not-null", groups={"weight-of-goods-carried"})
     * @Assert\PositiveOrZero(message="common.number.positive", groups={"weight-of-goods-carried"})
     * @Assert\Range(groups={"weight-of-goods-carried"}, max=2000000000, maxMessage="common.number.max")
     */
    private $weightOfGoodsCarried;

    /**
     * @ORM\ManyToOne(targetEntity=Trip::class, inversedBy="consignments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trip;

    public function getId(): ?string
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


    public function mergeChanges(Consignment $consignment, StopRepository $stopRepository)
    {
        $this->setWeightOfGoodsCarried($consignment->getWeightOfGoodsCarried());
        $this->setGoodsDescription($consignment->getGoodsDescription());
        $this->setGoodsDescriptionOther($consignment->getGoodsDescriptionOther());
        $this->setCargoTypeCode($consignment->getCargoTypeCode());
        $this->setHazardousGoodsCode($consignment->getHazardousGoodsCode());

        if ($consignment->getLoadingStop()) {
            $this->setLoadingStop($stopRepository->find($consignment->getLoadingStop()->getId()));
        }
        if ($consignment->getUnloadingStop()) {
            $this->setUnloadingStop($stopRepository->find($consignment->getUnloadingStop()->getId()));
        }
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

    public function getBlameLogLabel()
    {
        return $this->getGoodsDescriptionOther() ?? $this->getGoodsDescription();
    }

    public function getAssociatedEntityClass()
    {
        return Trip::class;
    }

    public function getAssociatedEntityId()
    {
        return $this->getTrip()->getId();
    }
}
