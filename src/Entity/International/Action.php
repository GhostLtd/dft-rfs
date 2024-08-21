<?php

namespace App\Entity\International;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoTypeInterface;
use App\Entity\CargoTypeTrait;
use App\Entity\CountryInterface;
use App\Entity\GoodsDescriptionInterface;
use App\Entity\HazardousGoodsInterface;
use App\Entity\HazardousGoodsTrait;
use App\Form\CountryType;
use App\Repository\International\ActionRepository;
use App\Form\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

#[AppAssert\CanBeUnloaded(groups: ["action-place"])]
#[AppAssert\UnloadedWeight(groups: ["action-unloaded-weight", "admin_action_unload"])]
#[AppAssert\Country(groups: ["action-place", "admin_action_unload", "admin_action_load"])]
#[ORM\Table(name: 'international_action')]
#[ORM\Entity(repositoryClass: ActionRepository::class)]
class Action implements CargoTypeInterface, CountryInterface, GoodsDescriptionInterface, HazardousGoodsInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $number = null;

    #[Assert\NotBlank(message: 'international.action.place.not-blank', groups: ['action-place', 'admin_action_unload', 'admin_action_load'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['action-place', 'admin_action_unload', 'admin_action_load'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 2, maxMessage: 'common.string.max-length', groups: ['action-place', 'admin_action_unload', 'admin_action_load'])]
    #[ORM\Column(type: Types::STRING, length: 2, nullable: true)]
    private ?string $country = null;

    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['action-place', 'admin_action_unload', 'admin_action_load'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $countryOther = null;

    #[Assert\NotNull(message: 'international.action.goods-type.invalid', groups: ['goods-description', 'admin_action_load'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $goodsDescription = null;

    #[Assert\Expression("(this.getGoodsDescription() != constant('App\\\\Entity\\\\AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER')) || value != null", message: 'common.goods-description-other.not-blank', groups: ['goods-description', 'admin_action_load'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['goods-description', 'admin_action_load'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $goodsDescriptionOther = null;

    #[Assert\NotNull(message: 'international.action.unloaded.all-not-null', groups: ['action-unloaded-weight', 'admin_action_unload'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $weightUnloadedAll = null;

    // N.B. action-unloaded-weight validation is performed by UnloadedWeight class validator
    #[Assert\NotBlank(message: 'international.action.goods-weight.not-blank', groups: ['action-loaded-weight', 'admin_action_load'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive', groups: ['action-loaded-weight', 'admin_action_load'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['action-loaded-weight', 'admin_action_load'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $weightOfGoods = null;

    use HazardousGoodsTrait;
    use CargoTypeTrait;

    #[Assert\NotNull(message: 'international.action.load-or-unload.not-null', groups: ['action-place'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $loading = null;

    #[Assert\NotNull(message: 'international.action.loading-place.not-null', groups: ['action-loading-place', 'admin_action_unload'])]
    #[ORM\ManyToOne(targetEntity: Action::class, inversedBy: 'unloadingActions')]
    private ?Action $loadingAction = null;

    /**
     * @var Collection<int, Action>
     */
    #[ORM\OneToMany(mappedBy: 'loadingAction', targetEntity: Action::class, cascade: ['remove'])]
    private Collection $unloadingActions;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: Trip::class, inversedBy: 'actions')]
    private ?Trip $trip = null;

    public function __construct()
    {
        $this->unloadingActions = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
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

    #[\Override]
    public function getCountry(): ?string
    {
        return $this->country;
    }

    #[\Override]
    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    #[\Override]
    public function getCountryOther(): ?string
    {
        return $this->countryOther;
    }

    #[\Override]
    public function setCountryOther(?string $countryOther): self
    {
        if ($this->country === CountryType::OTHER) {
            $this->countryOther = $countryOther;
        } else {
            $this->countryOther = null;
        }

        return $this;
    }

    #[\Override]
    public function getGoodsDescription(): ?string
    {
        return $this->goodsDescription;
    }

    #[\Override]
    public function setGoodsDescription(?string $goodsDescription): self
    {
        $this->goodsDescription = $goodsDescription;
        return $this;
    }

    #[\Override]
    public function getGoodsDescriptionOther(): ?string
    {
        return $this->goodsDescriptionOther;
    }

    #[\Override]
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
     * @return Collection<Action>
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

    public function mergeActionChanges(Action $action): void
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
        return $this->unloadingActions->filter(
            fn(Action $action) => $action->getId() !== $excludedId
        )->count();
    }
}
