<?php

namespace App\Entity;

use App\Repository\InternationalTripRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternationalTripRepository::class)
 */
class InternationalTrip
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $outboundDate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outboundUkPort;

    /**
     * @ORM\Column(type="boolean")
     */
    private $outboundWasLimitedBySpace;

    /**
     * @ORM\Column(type="boolean")
     */
    private $outboundWasLimitedByWeight;

    /**
     * @ORM\Column(type="boolean")
     */
    private $outboundWasEmpty;

    /**
     * @ORM\Column(type="date")
     */
    private $returnDate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $returnForeignPort;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $returnUkPort;

    /**
     * @ORM\Column(type="boolean")
     */
    private $returnWasLimitedBySpace;

    /**
     * @ORM\Column(type="boolean")
     */
    private $returnWasLimitedByWeight;

    /**
     * @ORM\Column(type="boolean")
     */
    private $returnWasEmpty;

    /**
     * @ORM\Column(type="integer")
     */
    private $roundTripDistance;

    /**
     * @ORM\OneToMany(targetEntity=InternationalStop::class, mappedBy="trip", orphanRemoval=true)
     */
    private $stops;

    /**
     * @ORM\Column(type="json")
     */
    private $countriesTransitted = [];

    /**
     * @ORM\OneToOne(targetEntity=InternationalVehicle::class, inversedBy="trip", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $vehicle;

    public function __construct()
    {
        $this->stops = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOutboundDate(): ?DateTimeInterface
    {
        return $this->outboundDate;
    }

    public function setOutboundDate(DateTimeInterface $outboundDate): self
    {
        $this->outboundDate = $outboundDate;

        return $this;
    }

    public function getOutboundUkPort(): ?string
    {
        return $this->outboundUkPort;
    }

    public function setOutboundUkPort(string $outboundUkPort): self
    {
        $this->outboundUkPort = $outboundUkPort;

        return $this;
    }

    public function getOutboundWasLimitedBySpace(): ?bool
    {
        return $this->outboundWasLimitedBySpace;
    }

    public function setOutboundWasLimitedBySpace(bool $outboundWasLimitedBySpace): self
    {
        $this->outboundWasLimitedBySpace = $outboundWasLimitedBySpace;

        return $this;
    }

    public function getOutboundWasLimitedByWeight(): ?bool
    {
        return $this->outboundWasLimitedByWeight;
    }

    public function setOutboundWasLimitedByWeight(bool $outboundWasLimitedByWeight): self
    {
        $this->outboundWasLimitedByWeight = $outboundWasLimitedByWeight;

        return $this;
    }

    public function getOutboundWasEmpty(): ?bool
    {
        return $this->outboundWasEmpty;
    }

    public function setOutboundWasEmpty(bool $outboundWasEmpty): self
    {
        $this->outboundWasEmpty = $outboundWasEmpty;

        return $this;
    }

    public function getReturnDate(): ?DateTimeInterface
    {
        return $this->returnDate;
    }

    public function setReturnDate(DateTimeInterface $returnDate): self
    {
        $this->returnDate = $returnDate;

        return $this;
    }

    public function getReturnForeignPort(): ?string
    {
        return $this->returnForeignPort;
    }

    public function setReturnForeignPort(string $returnForeignPort): self
    {
        $this->returnForeignPort = $returnForeignPort;

        return $this;
    }

    public function getReturnUkPort(): ?string
    {
        return $this->returnUkPort;
    }

    public function setReturnUkPort(string $returnUkPort): self
    {
        $this->returnUkPort = $returnUkPort;

        return $this;
    }

    public function getReturnWasLimitedBySpace(): ?bool
    {
        return $this->returnWasLimitedBySpace;
    }

    public function setReturnWasLimitedBySpace(bool $returnWasLimitedBySpace): self
    {
        $this->returnWasLimitedBySpace = $returnWasLimitedBySpace;

        return $this;
    }

    public function getReturnWasLimitedByWeight(): ?bool
    {
        return $this->returnWasLimitedByWeight;
    }

    public function setReturnWasLimitedByWeight(bool $returnWasLimitedByWeight): self
    {
        $this->returnWasLimitedByWeight = $returnWasLimitedByWeight;

        return $this;
    }

    public function getReturnWasEmpty(): ?bool
    {
        return $this->returnWasEmpty;
    }

    public function setReturnWasEmpty(bool $returnWasEmpty): self
    {
        $this->returnWasEmpty = $returnWasEmpty;

        return $this;
    }

    public function getRoundTripDistance(): ?int
    {
        return $this->roundTripDistance;
    }

    public function setRoundTripDistance(int $roundTripDistance): self
    {
        $this->roundTripDistance = $roundTripDistance;

        return $this;
    }

    /**
     * @return Collection|InternationalStop[]
     */
    public function getStops(): Collection
    {
        return $this->stops;
    }

    public function addStop(InternationalStop $stop): self
    {
        if (!$this->stops->contains($stop)) {
            $this->stops[] = $stop;
            $stop->setTrip($this);
        }

        return $this;
    }

    public function removeStop(InternationalStop $stop): self
    {
        if ($this->stops->removeElement($stop)) {
            // set the owning side to null (unless already changed)
            if ($stop->getTrip() === $this) {
                $stop->setTrip(null);
            }
        }

        return $this;
    }

    public function getCountriesTransitted(): ?array
    {
        return $this->countriesTransitted;
    }

    public function setCountriesTransitted(array $countriesTransitted): self
    {
        $this->countriesTransitted = $countriesTransitted;

        return $this;
    }

    public function getVehicle(): ?InternationalVehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(InternationalVehicle $vehicle): self
    {
        $this->vehicle = $vehicle;

        return $this;
    }
}
