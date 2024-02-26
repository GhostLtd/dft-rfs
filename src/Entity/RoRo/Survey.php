<?php

namespace App\Entity\RoRo;

use App\Entity\IdTrait;
use App\Entity\NoteInterface;
use App\Entity\Route\Route;
use App\Entity\SurveyNotesInterface;
use App\Entity\SurveyStateInterface;
use App\Repository\RoRo\SurveyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table(name: 'roro_survey')]
#[ORM\UniqueConstraint(name: 'roro_operation_route_date_index', columns: ['operator_id', 'route_id', 'survey_period_start'])]
#[ORM\Entity(repositoryClass: SurveyRepository::class)]
class Survey implements SurveyNotesInterface, SurveyStateInterface
{
    public const STATE_FILTER_CHOICES = [
        self::STATE_NEW,
        self::STATE_IN_PROGRESS,
        self::STATE_CLOSED,
        self::STATE_APPROVED,
    ];

    use IdTrait;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $surveyPeriodStart = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: Operator::class)]
    private ?Operator $operator = null;

    #[Assert\Expression('this.getIsActiveForPeriod() !== true or this.getDataEntryMethod() !== null', message: 'roro.survey.data-entry-method.not-null', groups: ['roro.data-entry-method'])]
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $dataEntryMethod = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private ?string $state = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: Route::class, inversedBy: 'surveys')]
    private ?Route $route = null;

    /**
     * @var Collection<int, VehicleCount>
     */
    #[Assert\Valid(groups: ['roro.vehicle-counts'])]
    #[ORM\OneToMany(mappedBy: 'survey', targetEntity: VehicleCount::class, cascade: ['persist'], orphanRemoval: true, indexBy: 'id')]
    #[ORM\OrderBy(['otherCode' => 'ASC', 'countryCode' => 'ASC'])]
    private Collection $vehicleCounts;

    #[Assert\NotNull(message: 'common.choice.not-null', groups: ['roro.is-active'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $isActiveForPeriod = null;

    /**
     * @var Collection<SurveyNote>
     */
    #[ORM\OneToMany(targetEntity: SurveyNote::class, mappedBy: 'survey')]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $notes;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $firstReminderSentDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $secondReminderSentDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    private ?DataEntryDebugInfo $dataEntryDebugInfo = null;

    #[Assert\Callback(groups: ['roro.vehicle-counts'])]
    public function validateVehicleCount(ExecutionContextInterface $context): void
    {
        foreach($this->vehicleCounts as $vehicleCount) {
            $count = $vehicleCount->getVehicleCount();

            if ($count === null) {
                continue;
            }

            $count = intval($count);
            if ($count < 0) {
                $countsPath = $vehicleCount->getOtherCode() === null ?
                    'countryVehicleCounts' :
                    'otherVehicleCounts';

                $context
                    ->buildViolation('roro.survey.vehicle-count.positive')
                    ->atPath("{$countsPath}[{$vehicleCount->getId()}].vehicleCount")
                    ->addViolation();
            }
        }
    }

    #[Assert\Callback(groups: ['roro.survey.data-entry'])]
    public function validateDataEntry(ExecutionContextInterface $context): void
    {
        $debug = $this->getDataEntryDebugInfo();

        if (!$debug) {
            return;
        }

        foreach($debug->getUnusedRows() as $unusedRow) {
            $context
                ->buildViolation('roro.data-entry.unused-row', $unusedRow)
                ->atPath("data")
                ->addViolation();
        }

        $checkTotal = function(?int $expected, int $actual, string $message) use ($context) : void {
            if ($expected && $expected !== $actual) {
                $context
                    ->buildViolation($message, [
                        'expected' => $expected,
                        'actual' => $actual,
                        'difference' => $expected - $actual,
                    ])
                    ->atPath("data")
                    ->addViolation();
            }
        };

        [
            'total_powered' => $actualTotalPowered,
            'total' => $actualTotal,
        ] = $this->getTotalVehicleCounts();

        $checkTotal($debug->getTotalVehicles(), $actualTotal, 'roro.data-entry.incorrect-total');
        $checkTotal($debug->getTotalPoweredVehicles(), $actualTotalPowered, 'roro.data-entry.incorrect-total-powered');
    }

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->vehicleCounts = new ArrayCollection();
    }

    public function getSurveyPeriodStart(): ?\DateTime
    {
        return $this->surveyPeriodStart;
    }

    public function setSurveyPeriodStart(\DateTime $surveyPeriodStart): self
    {
        $this->surveyPeriodStart = $surveyPeriodStart;
        return $this;
    }

    public function getOperator(): ?Operator
    {
        return $this->operator;
    }

    public function setOperator(?Operator $operator): self
    {
        $this->operator = $operator;
        return $this;
    }

    #[\Override]
    public function getState(): ?string
    {
        return $this->state;
    }

    #[\Override]
    public function setState(?string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getRoute(): ?Route
    {
        return $this->route;
    }

    public function setRoute(?Route $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getDataEntryMethod(): ?string
    {
        return $this->dataEntryMethod;
    }

    public function setDataEntryMethod(?string $dataEntryMethod): self
    {
        $this->dataEntryMethod = $dataEntryMethod;
        return $this;
    }

    /**
     * @return Collection<int, VehicleCount>
     */
    public function getVehicleCounts(): Collection
    {
        return $this->vehicleCounts;
    }

    /** @return Collection<int, VehicleCount> */
    public function getCountryVehicleCounts(): Collection
    {
        return $this->vehicleCounts->filter(fn(VehicleCount $cvc) => $cvc->getCountryCode() !== null)->matching($this->getVehicleCountOrderCriteria());
    }

    public function getVehicleCountByOtherCode(string $otherCode): ?VehicleCount
    {
        foreach($this->vehicleCounts as $vehicleCount) {
            if ($vehicleCount->getOtherCode() === $otherCode) {
                return $vehicleCount;
            }
        }

        return null;
    }

    public function getVehicleCountByCode(string $code): ?VehicleCount
    {
        foreach($this->vehicleCounts as $vehicleCount) {
            if ($vehicleCount->getOtherCode() === $code || $vehicleCount->getCountryCode() === $code) {
                return $vehicleCount;
            }
        }

        return null;
    }

    /** @return Collection<int, VehicleCount> */
    public function getOtherVehicleCounts(): Collection
    {
        return $this->vehicleCounts->filter(fn(VehicleCount $cvc) => $cvc->getCountryCode() === null)->matching($this->getVehicleCountOrderCriteria());
    }

    protected function getVehicleCountOrderCriteria(): Criteria
    {
        return Criteria::create()
            ->orderBy(["label" => 'ASC']);
    }

    public function addCountryVehicleCount(VehicleCount $countryVehicleCount): self
    {
        if (!$this->vehicleCounts->contains($countryVehicleCount)) {
            $this->vehicleCounts[] = $countryVehicleCount;
            $countryVehicleCount->setSurvey($this);
        }

        return $this;
    }

    public function addOtherVehicleCount(VehicleCount $otherVehicleCount): self
    {
        return $this->addCountryVehicleCount($otherVehicleCount);
    }

    public function removeCountryVehicleCount(VehicleCount $countryVehicleCount): self
    {
        if ($this->vehicleCounts->removeElement($countryVehicleCount)) {
            // set the owning side to null (unless already changed)
            if ($countryVehicleCount->getSurvey() === $this) {
                $countryVehicleCount->setSurvey(null);
            }
        }

        return $this;
    }

    public function removeOtherVehicleCount(VehicleCount $otherVehicleCount): self
    {
        return $this->removeCountryVehicleCount($otherVehicleCount);
    }

    public function getIsActiveForPeriod(): ?bool
    {
        return $this->isActiveForPeriod;
    }

    public function setIsActiveForPeriod(?bool $isActiveForPeriod): self
    {
        $this->isActiveForPeriod = $isActiveForPeriod;
        if ($isActiveForPeriod === false) {
            foreach ($this->vehicleCounts as $vehicleCount) {
                $vehicleCount->setVehicleCount(null);
            }
        }
        return $this;
    }

    #[\Override]
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    #[\Override]
    public function setNotes(Collection $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    #[\Override]
    public function addNote(NoteInterface $note): self
    {
        if (!$note instanceof SurveyNote) {
            throw new \RuntimeException("Got a ".$note::class.", but expected a ".SurveyNote::class);
        }

        if (!$this->notes->contains($note)) {
            $note->setSurvey($this);
            $this->notes[] = $note;
        }

        return $this;
    }

    #[\Override]
    public function createNote(): NoteInterface
    {
        return new SurveyNote();
    }

    public function getFirstReminderSentDate(): ?\DateTime
    {
        return $this->firstReminderSentDate;
    }

    public function setFirstReminderSentDate(?\DateTime $firstReminderSentDate): self
    {
        $this->firstReminderSentDate = $firstReminderSentDate;
        return $this;
    }

    public function getSecondReminderSentDate(): ?\DateTime
    {
        return $this->secondReminderSentDate;
    }

    public function setSecondReminderSentDate(?\DateTime $secondReminderSentDate): self
    {
        $this->secondReminderSentDate = $secondReminderSentDate;
        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;
        return $this;
    }

    public function getDataEntryDebugInfo(): ?DataEntryDebugInfo
    {
        return $this->dataEntryDebugInfo;
    }

    public function setDataEntryDebugInfo(?DataEntryDebugInfo $dataEntryDebugInfo): self
    {
        $this->dataEntryDebugInfo = $dataEntryDebugInfo;
        return $this;
    }

    // -----

    public function isCompleted(): bool
    {
        return in_array($this->state, [
            SurveyStateInterface::STATE_APPROVED,
            SurveyStateInterface::STATE_CLOSED,
            SurveyStateInterface::STATE_REJECTED,
        ]);
    }

    #[\Override]
    public function getChasedCount(): int
    {
        return array_reduce($this->getNotes()->toArray(), fn($c, NoteInterface $i) => $c + ($i->getWasChased() ? 1 : 0)) ?? 0;
    }

    public function merge(?Survey $survey): void
    {
        if ($survey) {
            foreach ($survey->getVehicleCounts() as $key => $item) {
                $this->vehicleCounts->get($key)->setVehicleCount($item->getVehicleCount());
            }
            $this->isActiveForPeriod = $survey->getIsActiveForPeriod();
            $this->dataEntryMethod = $survey->getDataEntryMethod();
        }
    }

    /**
     * @return array{total_powered: int, total: int}
     */
    public function getTotalVehicleCounts(): array
    {
        $sum = 0;
        foreach($this->getCountryVehicleCounts() as $vehicleCount) {
            $sum += ($vehicleCount->getVehicleCount() ?? 0);
        }

        $getOtherCount = function(string $type): int {
            foreach($this->getOtherVehicleCounts() as $vehicleCount) {
                if ($vehicleCount->getOtherCode() === $type) {
                    return $vehicleCount->getVehicleCount() ?? 0;
                }
            }

            return 0;
        };

        $sum += $getOtherCount(VehicleCount::OTHER_CODE_OTHER);
        $sum += $getOtherCount(VehicleCount::OTHER_CODE_UNKNOWN);

        $unaccompanied = $getOtherCount(VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS);

        return [
            'total_powered' => $sum,
            'total' => ($sum + $unaccompanied),
        ];
    }
}
