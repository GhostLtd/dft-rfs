<?php

namespace App\Entity\International;

use App\Entity\CargoTypeTrait;
use App\Entity\HazardousGoodsTrait;
use App\Repository\International\ActionRepository;
use App\Form\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ActionRepository::class)
 * @ORM\Table(name="international_action")
 *
 * @AppAssert\CanBeUnloaded(groups={"action-place"})
 * @AppAssert\UnloadedWeight(groups={"action-unloaded-weight"})
 */
class Action
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     */
    private $number;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"action-place"}, message="common.place.place")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"action-place"}, message="common.place.country")
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotNull(groups={"goods-description"}, message="common.choice.invalid")
     */
    private $goodsDescription;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Expression("(this.getGoodsDescription() != constant('App\\Entity\\AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER')) || value != null", message="common.goods-description-other.not-blank", groups={"goods-description"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"goods-description"})
     */
    private $goodsDescriptionOther;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(groups={"action-unloaded-weight"}, message="international.action.unloading.weight-not-null")
     * @Assert\Range(groups={"action-unloaded-weight"}, min=1, minMessage="international.action.unloading.weight-more-than-one")
     *
     * @Assert\NotBlank(message="common.number.not-null", groups={"action-loaded-weight"})
     * @Assert\Positive(message="common.number.positive", groups={"action-loaded-weight"})
     */
    private $weightOfGoods;

    use HazardousGoodsTrait;
    use CargoTypeTrait;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(groups={"action-place"}, message="common.choice.not-null")
     */
    private $loading;

    /**
     * @ORM\ManyToOne(targetEntity=Action::class, inversedBy="unloadingActions")
     * @Assert\NotNull(groups={"action-loading-place"}, message="common.choice.not-null")
     */
    private $loadingAction;

    /**
     * @ORM\OneToMany(targetEntity=Action::class, mappedBy="loadingAction")
     */
    private $unloadingActions;

    /**
     * @ORM\ManyToOne(targetEntity=Trip::class, inversedBy="actions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trip;

    public function __construct()
    {
        $this->unloadingActions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

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

    public function getGoodsDescriptionOther(): ?string
    {
        return $this->goodsDescriptionOther;
    }

    public function setGoodsDescriptionOther(?string $goodsDescriptionOther): self
    {
        $this->goodsDescriptionOther = $goodsDescriptionOther;

        return $this;
    }

    public function getWeightOfGoods(): ?int
    {
        return $this->weightOfGoods;
    }

    public function setWeightOfGoods(?int $weightOfGoods): self
    {
        $this->weightOfGoods = $weightOfGoods;

        return $this;
    }

    public function getLoading(): ?bool
    {
        return $this->loading;
    }

    public function setLoading(?bool $loading): self
    {
        $this->loading = $loading;

        return $this;
    }

    public function getLoadingAction(): ?self
    {
        return $this->loadingAction;
    }

    public function setLoadingAction(?self $loadingAction): self
    {
        $this->loadingAction = $loadingAction;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getUnloadingActions(): Collection
    {
        return $this->unloadingActions;
    }

    public function addUnloadingAction(self $unloadingAction): self
    {
        if (!$this->unloadingActions->contains($unloadingAction)) {
            $this->unloadingActions[] = $unloadingAction;
            $unloadingAction->setLoadingAction($this);
        }

        return $this;
    }

    public function removeUnloadingAction(self $unloadingAction): self
    {
        if ($this->unloadingActions->removeElement($unloadingAction)) {
            // set the owning side to null (unless already changed)
            if ($unloadingAction->getLoadingAction() === $this) {
                $unloadingAction->setLoadingAction(null);
            }
        }

        return $this;
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
}
