<?php

namespace App\Entity\International;

use App\Entity\Distance;
use App\Form\Validator as AppAssert;
use App\Repository\International\TripRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=TripRepository::class)
 * @ORM\Table(name="international_trip")
 */
class Trip
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank(groups={"trip_dates"})
     */
    private $outboundDate;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"trip_outbound_ports"})
     */
    private $outboundUkPort;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"trip_outbound_ports"})
     */
    private $outboundForeignPort;

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
     * @Assert\NotBlank(groups={"trip_dates"})
     */
    private $returnDate;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"trip_return_ports"})
     */
    private $returnForeignPort;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"trip_return_ports"})
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
     * @ORM\Embedded(class=Distance::class)
     * @AppAssert\ValidValueUnit(groups={"trip_distance"})
     */
    private $roundTripDistance;

    /**
     * @ORM\OneToMany(targetEntity=Stop::class, mappedBy="trip", orphanRemoval=true)
     * @ORM\OrderBy({"number" = "ASC"})
     * @var Stop[]|Collection
     */
    private $stops;

    /**
     * @ORM\Column(type="json")
     */
    private $countriesTransitted = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $countriesTransittedOther;

    /**
     * @ORM\ManyToOne(targetEntity=Vehicle::class, inversedBy="trips")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vehicle;

    /**
     * @ORM\OneToMany(targetEntity=Consignment::class, mappedBy="trip")
     */
    private $consignments;

    /**
     * @ORM\OneToMany(targetEntity=Action::class, mappedBy="trip")
     */
    private $actions;

    /**
     * @Assert\Callback(groups={"trip_outbound_cargo_state"})
     */
    public function validateOutboundCargoState(ExecutionContextInterface $context) {
        $this->validateCargoState($context, 'outbound');
    }

    /**
     * @Assert\Callback(groups={"trip_return_cargo_state"})
     */
    public function validateReturnCargoState(ExecutionContextInterface $context) {
        $this->validateCargoState($context, 'return');
    }



    /**
     * @Assert\Callback(groups={"trip_dates"})
     */
    public function validateDates(ExecutionContextInterface $context) {
        $outboundDate = $this->getOutboundDate();
        $returnDate = $this->getReturnDate();

        if (!$outboundDate && !$returnDate) {
            return;
        }

        if ($returnDate < $outboundDate) {
            $context->buildViolation('international.trip.dates.not-return-before-departure')
                ->atPath('returnDate')
                ->addViolation();
        }
    }

    protected function validateCargoState(ExecutionContextInterface $context, string $direction) {
        $accessor = PropertyAccess::createPropertyAccessor();

        $wasEmpty = $accessor->getValue($this, "{$direction}WasEmpty");
        $wasLimitedBySpace = $accessor->getValue($this, "{$direction}WasLimitedBySpace");
        $wasLimitedByWeight = $accessor->getValue($this, "{$direction}WasLimitedByWeight");

        if ($wasEmpty === null) {
            $context->buildViolation("international.trip.{$direction}-was-empty.not-null")
                ->atPath('wasEmpty')
                ->addViolation();
        } else if ($wasEmpty && ($wasLimitedByWeight || $wasLimitedBySpace)) {
            $context->buildViolation("international.trip.{$direction}-was-empty.cannot-be-both")
                ->atPath('wasEmpty')
                ->addViolation();
        }
    }

    public function __construct()
    {
        $this->stops = new ArrayCollection();
        $this->consignments = new ArrayCollection();
        $this->actions = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOutboundDate(): ?DateTimeInterface
    {
        return $this->outboundDate;
    }

    public function setOutboundDate(?DateTimeInterface $outboundDate): self
    {
        $this->outboundDate = $outboundDate;

        return $this;
    }

    public function getOutboundUkPort(): ?string
    {
        return $this->outboundUkPort;
    }

    public function setOutboundUkPort(?string $outboundUkPort): self
    {
        $this->outboundUkPort = $outboundUkPort;

        return $this;
    }

    public function getOutboundForeignPort(): ?string
    {
        return $this->outboundForeignPort;
    }

    public function setOutboundForeignPort(?string $outboundForeignPort): self
    {
        $this->outboundForeignPort = $outboundForeignPort;

        return $this;
    }

    public function getOutboundWasLimitedBySpace(): ?bool
    {
        return $this->outboundWasLimitedBySpace;
    }

    public function setOutboundWasLimitedBySpace(?bool $outboundWasLimitedBySpace): self
    {
        $this->outboundWasLimitedBySpace = $outboundWasLimitedBySpace;

        return $this;
    }

    public function getOutboundWasLimitedByWeight(): ?bool
    {
        return $this->outboundWasLimitedByWeight;
    }

    public function setOutboundWasLimitedByWeight(?bool $outboundWasLimitedByWeight): self
    {
        $this->outboundWasLimitedByWeight = $outboundWasLimitedByWeight;

        return $this;
    }

    public function getOutboundWasEmpty(): ?bool
    {
        return $this->outboundWasEmpty;
    }

    public function setOutboundWasEmpty(?bool $outboundWasEmpty): self
    {
        $this->outboundWasEmpty = $outboundWasEmpty;

        return $this;
    }

    public function getReturnDate(): ?DateTimeInterface
    {
        return $this->returnDate;
    }

    public function setReturnDate(?DateTimeInterface $returnDate): self
    {
        $this->returnDate = $returnDate;

        return $this;
    }

    public function getReturnForeignPort(): ?string
    {
        return $this->returnForeignPort;
    }

    public function setReturnForeignPort(?string $returnForeignPort): self
    {
        $this->returnForeignPort = $returnForeignPort;

        return $this;
    }

    public function getReturnUkPort(): ?string
    {
        return $this->returnUkPort;
    }

    public function setReturnUkPort(?string $returnUkPort): self
    {
        $this->returnUkPort = $returnUkPort;

        return $this;
    }

    public function getReturnWasLimitedBySpace(): ?bool
    {
        return $this->returnWasLimitedBySpace;
    }

    public function setReturnWasLimitedBySpace(?bool $returnWasLimitedBySpace): self
    {
        $this->returnWasLimitedBySpace = $returnWasLimitedBySpace;

        return $this;
    }

    public function getReturnWasLimitedByWeight(): ?bool
    {
        return $this->returnWasLimitedByWeight;
    }

    public function setReturnWasLimitedByWeight(?bool $returnWasLimitedByWeight): self
    {
        $this->returnWasLimitedByWeight = $returnWasLimitedByWeight;

        return $this;
    }

    public function getReturnWasEmpty(): ?bool
    {
        return $this->returnWasEmpty;
    }

    public function setReturnWasEmpty(?bool $returnWasEmpty): self
    {
        $this->returnWasEmpty = $returnWasEmpty;

        return $this;
    }

    public function getRoundTripDistance(): ?Distance
    {
        return $this->roundTripDistance;
    }

    public function setRoundTripDistance(Distance $roundTripDistance): self
    {
        $this->roundTripDistance = $roundTripDistance;

        return $this;
    }

    /**
     * @return Collection|Stop[]
     */
    public function getStops(): Collection
    {
        return $this->stops;
    }

    public function addStop(Stop $stop): self
    {
        if (!$this->stops->contains($stop)) {
            $this->stops[] = $stop;
            $stop->setTrip($this);
            $this->renumberStops();
        }

        return $this;
    }

    public function removeStop(Stop $stop): self
    {
        if ($this->stops->removeElement($stop)) {
            // set the owning side to null (unless already changed)
            if ($stop->getTrip() === $this) {
                $stop->setTrip(null);
            }

            $this->renumberStops();
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

    public function getCountriesTransittedOther(): ?string
    {
        return $this->countriesTransittedOther;
    }

    public function setCountriesTransittedOther(?string $countriesTransittedOther): self
    {
        $this->countriesTransittedOther = $countriesTransittedOther;

        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    // -----

    public function getOutboundCargoState(): string {
        return $this->getCargoState($this->outboundWasEmpty, $this->outboundWasLimitedBySpace, $this->outboundWasLimitedByWeight);
    }

    public function getReturnCargoState(): string {
        return $this->getCargoState($this->returnWasEmpty, $this->returnWasLimitedBySpace, $this->returnWasLimitedByWeight);
    }

    protected function getCargoState(bool $wasEmpty, bool $limitedBySpace, bool $limitedByWeight): string {
        if ($wasEmpty) {
            return 'empty';
        }

        if ($limitedBySpace) {
            return $limitedByWeight ? 'limited-by-space-and-weight' : 'limited-by-space';
        }

        return $limitedByWeight ? 'limited-by-weight' : 'not-limited';
    }

    public function getAllCountriesTransitted(): string {
        $countries = array_unique(array_merge(
            array_map(function($code) {
                return Countries::getName($code);
            }, $this->countriesTransitted),
            array_map('trim', explode(',', $this->countriesTransittedOther))
        ));

        sort($countries);

        $countries = array_filter($countries, function($str) {
            return $str !== '';
        });

        return join(', ', $countries);
    }

    public function mergeTripChanges(Trip $trip)
    {
        $this->setOutboundDate($trip->getOutboundDate());
        $this->setReturnDate($trip->getReturnDate());
        $this->setOutboundUkPort($trip->getOutboundUkPort());
        $this->setOutboundForeignPort($trip->getOutboundForeignPort());
        $this->setReturnUkPort($trip->getReturnUkPort());
        $this->setReturnForeignPort($trip->getReturnForeignPort());
        $this->setRoundTripDistance($trip->getRoundTripDistance());
        $this->setCountriesTransitted($trip->getCountriesTransitted());
        $this->setCountriesTransittedOther($trip->getCountriesTransittedOther());
    }

    /**
     * @return Collection|Consignment[]
     */
    public function getConsignments(): Collection
    {
        return $this->consignments;
    }

    public function addConsignment(Consignment $consignment): self
    {
        if (!$this->consignments->contains($consignment)) {
            $this->consignments[] = $consignment;
            $consignment->setTrip($this);
        }

        return $this;
    }

    public function removeConsignment(Consignment $consignment): self
    {
        if ($this->consignments->removeElement($consignment)) {
            // set the owning side to null (unless already changed)
            if ($consignment->getTrip() === $this) {
                $consignment->setTrip(null);
            }
        }

        return $this;
    }

    public function getNextStopNumber(): int
    {
        return ($this->stops->last()) ? $this->stops->last()->getNumber() + 1 : 1;
    }

    protected function renumberStops(): void
    {
        $count = 1;
        foreach($this->stops as $stop) {
            $stop->setNumber($count++);
        }
    }

    /**
     * @return Collection|Action[]
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function addAction(Action $action): self
    {
        if (!$this->actions->contains($action)) {
            $this->actions[] = $action;
            $action->setTrip($this);
        }

        return $this;
    }

    public function removeAction(Action $action): self
    {
        if ($this->actions->removeElement($action)) {
            // set the owning side to null (unless already changed)
            if ($action->getTrip() === $this) {
                $action->setTrip(null);
            }
        }

        return $this;
    }
}
