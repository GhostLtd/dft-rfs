<?php

namespace App\Entity\International;

use App\Repository\International\ActionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ActionRepository::class)
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
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $goodsDescription;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $goodsDescriptionOther;

    /**
     * @ORM\Column(type="integer")
     */
    private $weightOfGoods;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $hazardousGoodsCode;

    /**
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    private $cargoTypeCode;

    /**
     * @ORM\Column(type="boolean")
     */
    private $loading;

    /**
     * @ORM\ManyToOne(targetEntity=Action::class, inversedBy="unloadingActions")
     */
    private $loadingAction;

    /**
     * @ORM\OneToMany(targetEntity=Action::class, mappedBy="loadingAction")
     */
    private $unloadingActions;

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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
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

    public function setWeightOfGoods(int $weightOfGoods): self
    {
        $this->weightOfGoods = $weightOfGoods;

        return $this;
    }

    public function getHazardousGoodsCode(): ?string
    {
        return $this->hazardousGoodsCode;
    }

    public function setHazardousGoodsCode(?string $hazardousGoodsCode): self
    {
        $this->hazardousGoodsCode = $hazardousGoodsCode;

        return $this;
    }

    public function getCargoTypeCode(): ?string
    {
        return $this->cargoTypeCode;
    }

    public function setCargoTypeCode(?string $cargoTypeCode): self
    {
        $this->cargoTypeCode = $cargoTypeCode;

        return $this;
    }

    public function getLoading(): ?bool
    {
        return $this->loading;
    }

    public function setLoading(bool $loading): self
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
}
