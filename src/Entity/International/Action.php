<?php

namespace App\Entity\International;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoTypeTrait;
use App\Entity\CountryInterface;
use App\Entity\GoodsDescriptionInterface;
use App\Entity\HazardousGoodsTrait;
use App\Form\CountryType;
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
 * @AppAssert\UnloadedWeight(groups={"action-unloaded-weight", "admin_action_unload"})
 * @AppAssert\Country(groups={"action-place", "admin_action_unload", "admin_action_load"})
 */
class Action implements GoodsDescriptionInterface, CountryInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     */
    private $number;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"action-place", "admin_action_unload", "admin_action_load"}, message="international.action.place.not-blank")
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"action-place", "admin_action_unload", "admin_action_load"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     * @Assert\Length(max=2, maxMessage="common.string.max-length", groups={"action-place", "admin_action_unload", "admin_action_load"})
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"action-place", "admin_action_unload", "admin_action_load"})
     */
    private $countryOther;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotNull(groups={"goods-description", "admin_action_load"}, message="international.action.goods-type.invalid")
     */
    private $goodsDescription;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Expression("(this.getGoodsDescription() != constant('App\\Entity\\AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER')) || value != null", message="common.goods-description-other.not-blank", groups={"goods-description", "admin_action_load"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"goods-description", "admin_action_load"})
     */
    private $goodsDescriptionOther;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Assert\NotNull(groups={"action-unloaded-weight", "admin_action_unload"}, message="international.action.unloaded.all-not-null")
     */
    private $weightUnloadedAll;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * N.B. action-unloaded-weight validation is performed by UnloadedWeight class validator
     * @Assert\NotBlank(message="international.action.goods-weight.not-blank", groups={"action-loaded-weight", "admin_action_load"})
     * @Assert\PositiveOrZero(message="common.number.positive", groups={"action-loaded-weight", "admin_action_load"})
     * @Assert\Range(groups={"action-loaded-weight", "admin_action_load"}, max=2000000000, maxMessage="common.number.max")
     */
    private $weightOfGoods;

    use HazardousGoodsTrait;
    use CargoTypeTrait;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(groups={"action-place"}, message="international.action.load-or-unload.not-null")
     */
    private $loading;

    /**
     * @ORM\ManyToOne(targetEntity=Action::class, inversedBy="unloadingActions")
     * @Assert\NotNull(groups={"action-loading-place", "admin_action_unload"}, message="international.action.loading-place.not-null")
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

    public function getId(): ?string
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

    public function getCountryOther(): ?string
    {
        return $this->countryOther;
    }

    public function setCountryOther(?string $countryOther): self
    {
        if ($this->country === CountryType::OTHER) {
            $this->countryOther = $countryOther;
        } else {
            $this->countryOther = null;
        }

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

    public function getGoodsDescriptionNormalized(): ?string
    {
        if ($this->goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER) {
            return $this->getGoodsDescriptionOther();
        }
        return $this->getGoodsDescription();
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

    public function getWeightUnloadedAll(): ?bool
    {
        return $this->weightUnloadedAll;
    }

    public function setWeightUnloadedAll(?bool $weightUnloadedAll): self
    {
        $this->weightUnloadedAll = $weightUnloadedAll;

        if ($weightUnloadedAll) {
            $this->setWeightOfGoods(null);
        }

        return $this;
    }

    // -----

    public function mergeActionChanges(Action $action)
    {
        $this->setName($action->getName());
        $this->setCountry($action->getCountry());
        $this->setCountryOther($action->getCountryOther());
        $this->setGoodsDescription($action->getGoodsDescription());
        $this->setGoodsDescriptionOther($action->getGoodsDescriptionOther());
        $this->setWeightOfGoods($action->getWeightOfGoods());
        $this->setWeightUnloadedAll($action->getWeightUnloadedAll());
        $this->setHazardousGoodsCode($action->getHazardousGoodsCode());
        $this->setCargoTypeCode($action->getCargoTypeCode());
        $this->setLoadingAction($action->getLoadingAction());
    }

    public function getUnloadingActionCountExcluding(Action $excludedAction): int
    {
        $excludedId = $excludedAction->getId();
        return $this->unloadingActions->filter(function(Action $action) use ($excludedId) {
            return $action->getId() !== $excludedId;
        })->count();
    }
}
