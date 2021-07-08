<?php

namespace App\Entity\International;

use App\Entity\Distance;
use App\Entity\SimpleVehicleTrait;
use App\Entity\Vehicle as VehicleLookup;
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
 * @AppAssert\TripRoute(message="international.trip.outbound.ports-not-null", groups={"trip_outbound_ports"}, direction="outbound")
 * @AppAssert\TripRoute(message="international.trip.return.ports-not-null", groups={"trip_return_ports"}, direction="return")
 * @AppAssert\TripRoute(groups={"admin_trip"}, direction="outbound", formField="outboundPorts")
 * @AppAssert\TripRoute(groups={"admin_trip"}, direction="return", formField="returnPorts")
 */
class Trip
{
    use SimpleVehicleTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="international.trip.origin.not-blank", groups={"trip_places", "admin_trip"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"trip_places", "admin_trip"})
     */
    private $origin;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="international.trip.destination.not-blank", groups={"trip_places", "admin_trip"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"trip_places", "admin_trip"})
     */
    private $destination;


    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank(groups={"trip_dates", "admin_trip"}, message="international.trip.outbound.date-not-null")
     */
    private $outboundDate;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"trip_outbound_ports", "admin_trip"})
     */
    private $outboundUkPort;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"trip_outbound_ports", "admin_trip"})
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
     * @Assert\NotBlank(groups={"trip_dates", "admin_trip"}, message="international.trip.return.date-not-null")
     */
    private $returnDate;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"trip_return_ports", "admin_trip"})
     */
    private $returnForeignPort;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"trip_return_ports", "admin_trip"})
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
     * @AppAssert\ValidValueUnit(groups={"trip_distance", "admin_trip"})
     */
    private $roundTripDistance;

    /**
     * @ORM\Column(type="json")
     */
    private $countriesTransitted = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Length(max=2000, maxMessage="common.string.max-length", groups={"trip_countries_transitted", "admin_trip"})
     */
    private $countriesTransittedOther;

    /**
     * @ORM\ManyToOne(targetEntity=Vehicle::class, inversedBy="trips")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vehicle;

    /**
     * @ORM\OneToMany(targetEntity=Action::class, mappedBy="trip")
     * @ORM\OrderBy({"number" = "ASC"})
     */
    private $actions;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(message="international.trip.trailer-swap.not-null", groups={"trip_swapped_trailer"})
     */
    private $isSwappedTrailer;

    /**
     * Redefined here for Validation purposes
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Expression(
     *     expression="(this.getIsSwappedTrailer() === false) || !is_empty(value)",
     *     groups={"trip_swapped_trailer"},
     *     message="common.vehicle.axle-configuration.not-blank"
     * )
     */
    private $axleConfiguration;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isChangedBodyType;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Assert\Expression(
     *     expression="(this.getIsChangedBodyType() === false) || !is_empty(value)",
     *     groups={"trip_changed_body_type"},
     *     message="common.vehicle.body-type.not-blank"
     * )
     */
    private $bodyType;

    /**
     * @Assert\Callback(groups={"trip_outbound_cargo_state", "admin_trip"})
     */
    public function validateOutboundCargoState(ExecutionContextInterface $context) {
        $this->validateCargoState($context, 'outbound');
    }

    /**
     * @Assert\Callback(groups={"trip_return_cargo_state", "admin_trip"})
     */
    public function validateReturnCargoState(ExecutionContextInterface $context) {
        $this->validateCargoState($context, 'return');
    }

    /**
     * @Assert\Callback(groups={"trip_dates", "admin_trip"})
     */
    public function validateDates(ExecutionContextInterface $context) {
        $outboundDate = $this->getOutboundDate();
        $returnDate = $this->getReturnDate();

        $survey = $this->getVehicle()->getSurveyResponse()->getSurvey();
        $start = $survey->getSurveyPeriodStart();
        $end = $survey->getSurveyPeriodEnd();

        if ($outboundDate && ($outboundDate < $start || $outboundDate > $end)) {
            $context->buildViolation('international.trip.dates.outside-period')
                ->atPath('outboundDate')
                ->addViolation();
        }

        if (!$outboundDate || !$returnDate) {
            return;
        }

        if ($returnDate < $outboundDate) {
            $context->buildViolation('international.trip.dates.not-return-before-departure')
                ->atPath('returnDate')
                ->addViolation();
        }
    }

    /**
     * @Assert\Callback(groups={"admin_trip"})
     * @param ExecutionContextInterface $context
     */
    public function validateWeightsAdminOnly(ExecutionContextInterface $context) {
        if (!$this->canChangeWeights()) {
            return;
        }

        if (empty($this->carryingCapacity)) {
            $context->buildViolation('international.trip.dates.outside-period')
                ->atPath('carrying_capacity')
                ->addViolation();
        }

        if (empty($this->grossWeight)) {
            $context->buildViolation('international.trip.dates.outside-period')
                ->atPath('gross_weight')
                ->addViolation();
        }
    }

    protected function validateCargoState(ExecutionContextInterface $context, string $direction) {
        $accessor = PropertyAccess::createPropertyAccessor();

        $wasEmpty = $accessor->getValue($this, "{$direction}WasEmpty");
        $wasLimitedBySpace = $accessor->getValue($this, "{$direction}WasLimitedBySpace");
        $wasLimitedByWeight = $accessor->getValue($this, "{$direction}WasLimitedByWeight");

        if ($wasEmpty === null) {
            $context->buildViolation("international.trip.{$direction}.was-empty.not-null")
                ->atPath('wasEmpty')
                ->addViolation();
        } else if ($wasEmpty && ($wasLimitedByWeight || $wasLimitedBySpace)) {
            $context->buildViolation("international.trip.{$direction}.was-empty.cannot-be-both")
                ->atPath('wasEmpty')
                ->addViolation();
        }
    }

    public function __construct()
    {
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
            $this->renumberActions();
        }

        return $this;
    }

    public function removeAction(Action $action): self
    {
        $this->actions->removeElement($action);
        return $this;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(?string $origin): self
    {
        $this->origin = $origin;
        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): self
    {
        $this->destination = $destination;
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
        $this->setOrigin($trip->getOrigin());
        $this->setDestination($trip->getDestination());
        $this->setOutboundDate($trip->getOutboundDate());
        $this->setReturnDate($trip->getReturnDate());
        $this->setOutboundUkPort($trip->getOutboundUkPort());
        $this->setOutboundForeignPort($trip->getOutboundForeignPort());
        $this->setReturnUkPort($trip->getReturnUkPort());
        $this->setReturnForeignPort($trip->getReturnForeignPort());
        $this->setRoundTripDistance($trip->getRoundTripDistance());
        $this->setCountriesTransitted($trip->getCountriesTransitted());
        $this->setCountriesTransittedOther($trip->getCountriesTransittedOther());

        $this->setIsSwappedTrailer($trip->getIsSwappedTrailer());
        $this->setAxleConfiguration($trip->getAxleConfiguration());
        $this->setIsChangedBodyType($trip->getIsChangedBodyType());
        $this->setBodyType($trip->getBodyType());
    }

    public function getNextActionNumber(): int
    {
        return ($this->actions->last()) ? $this->actions->last()->getNumber() + 1 : 1;
    }

    public function renumberActions(): void
    {
        $count = 1;
        foreach($this->actions as $action) {
            $action->setNumber($count++);
        }
    }

    public function getIsSwappedTrailer(): ?bool
    {
        return $this->isSwappedTrailer;
    }

    public function setIsSwappedTrailer(?bool $isSwappedTrailer): self
    {
        $this->isSwappedTrailer = $isSwappedTrailer;

        return $this;
    }

    public function getChangeBodyTypeChoices()
    {
        $choices = VehicleLookup::BODY_CONFIGURATION_CHOICES;
        $vehicleBodyType = $this->getVehicle()->getBodyType();

        // filter original choice
        $choices = array_filter($choices, function($value) use ($vehicleBodyType){
            return $value !== $vehicleBodyType;
        });

        return $choices;
    }

    public function getTrailerSwapChoices()
    {
        $choices = [];
        $vehicleTrailerConfig = $this->getVehicle()->getTrailerConfiguration();

        switch ($vehicleTrailerConfig) {
            case VehicleLookup::TRAILER_CONFIGURATION_ARTICULATED :
                $choices = VehicleLookup::AXLE_CONFIGURATION_CHOICES[VehicleLookup::TRAILER_CONFIGURATION_ARTICULATED];
                break;
            case VehicleLookup::TRAILER_CONFIGURATION_RIGID_TRAILER :
            case VehicleLookup::TRAILER_CONFIGURATION_RIGID :
                $choices = array_merge(
                    VehicleLookup::AXLE_CONFIGURATION_CHOICES[VehicleLookup::TRAILER_CONFIGURATION_RIGID],
                    VehicleLookup::AXLE_CONFIGURATION_CHOICES[VehicleLookup::TRAILER_CONFIGURATION_RIGID_TRAILER]
                );
                break;
        }

        $vehicleAxleConfig = $this->getVehicle()->getAxleConfiguration();

        // filter original choice
        $choices = array_filter($choices, function($value) use ($vehicleAxleConfig){
            return $value !== $vehicleAxleConfig;
        });

        // Remove Rigid other (unless original was rigid other + trailer) as we can never swap to that!
        $choices = array_filter($choices, function($value) use ($vehicleAxleConfig){
            return $value !== 199 || $vehicleAxleConfig === 299;
        });

        // filter the choices so only the middle digit matches (or other in same category)
        $choices = array_filter($choices, function($value) use ($vehicleAxleConfig){
            return substr($value, 1, 1) === substr($vehicleAxleConfig, 1, 1)
                || substr($value, 1, 2) === '99';
        });

        return $choices;
    }

    public function getIsChangedBodyType(): ?bool
    {
        return $this->isChangedBodyType;
    }

    public function setIsChangedBodyType(?bool $isChangedBodyType): self
    {
        $this->isChangedBodyType = $isChangedBodyType;

        return $this;
    }

    public function canChangeBodyType()
    {
        if (!$this->getVehicle()) return false;
        return $this->getVehicle()->getTrailerConfiguration() === VehicleLookup::TRAILER_CONFIGURATION_ARTICULATED;
    }

    /**
     * This logic should exactly mirror that in
     * javascript GOVUK.rfsIrhsTrip.canChangeWeights()
     */
    public function canChangeWeights()
    {
        return ($this->isSwappedTrailer || $this->isChangedBodyType);
    }
}
