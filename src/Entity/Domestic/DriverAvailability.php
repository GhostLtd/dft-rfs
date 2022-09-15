<?php

namespace App\Entity\Domestic;

use App\Entity\CurrencyOrPercentage;
use App\Repository\Domestic\DriverAvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Form\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=DriverAvailabilityRepository::class)
 * @ORM\Table("domestic_driver_availablity")
 *
 * @AppAssert\OtherNotBlank(
 *     selectField="reasonsForDriverVacancies",
 *     groups={"driver-availability.drivers-and-vacancies"},
 *     message="driver-availability.reasons-for-driver-vacancies.other"
 * )
 *
 * @AppAssert\OtherNotBlank(
 *     selectField="wageIncreasePeriod",
 *     groups={"driver-availability.wages"},
 *     message="driver-availability.wages.period.other"
 * )
 *
 * @AppAssert\OtherNotBlank(
 *     selectField="reasonsForWageIncrease",
 *     groups={"driver-availability.wages"},
 *     message="driver-availability.wages.increase-reason.other"
 * )
 *
 * @AppAssert\DriverAvailabilityWagesPeriod(
 *     triggerField="averageWageIncrease",
 *     targetField="wageIncreasePeriod",
 *     groups={"driver-availability.wages"},
 *     message="driver-availability.wages.increase-period"
 * )
 */
class DriverAvailability
{
    const VACANCY_REASON_TRANSLATION_PREFIX = 'domestic.driver-availability.drivers.reasons-for-vacancies.choices.';
    const VACANCY_REASON_CHOICES = [
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'lured-away' => 'lured-away',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'covid' => 'covid',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'retirement' => 'retirement',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'existing-drivers-leaving' => 'existing-drivers-leaving',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'new-work' => 'new-work',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'lack-of-eu-drivers' => 'lack-of-eu-drivers',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'no-new-drivers' => 'no-new-drivers',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'ir35-changes' => 'ir35-changes',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'unavailable-tests' => 'unavailable-tests',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'working-conditions' => 'working-conditions',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'other' => 'other',
    ];

    const YES_NO_PLAN_TO_TRANSLATION_PREFIX = 'domestic.driver-availability.yes-no-plan-to.choices.';
    const YES_NO_PLAN_TO_CHOICES = [
        self::YES_NO_PLAN_TO_TRANSLATION_PREFIX . 'yes' => 'yes',
        self::YES_NO_PLAN_TO_TRANSLATION_PREFIX . 'no' => 'no',
        self::YES_NO_PLAN_TO_TRANSLATION_PREFIX . 'plan-to' => 'plan-to',
        self::YES_NO_PLAN_TO_TRANSLATION_PREFIX . 'do-not-know' => 'do-not-know',
    ];

    const WAGE_INCREASE_REASON_TRANSLATION_PREFIX = 'domestic.driver-availability.wages.reasons-for-wage-increase.choices.';
    const WAGE_INCREASE_REASON_CHOICES = [
        self::WAGE_INCREASE_REASON_TRANSLATION_PREFIX . 'planned' => 'planned',
        self::WAGE_INCREASE_REASON_TRANSLATION_PREFIX . 'retain-existing' => 'retain-existing',
        self::WAGE_INCREASE_REASON_TRANSLATION_PREFIX . 'attract-new' => 'attract-new',
        self::WAGE_INCREASE_REASON_TRANSLATION_PREFIX . 'other' => 'other',
    ];

    const WAGE_INCREASE_PERIOD_TRANSLATION_PREFIX = 'domestic.driver-availability.wages.wage-increase-period.choices.';
    const WAGE_INCREASE_PERIOD_CHOICES = [
        self::WAGE_INCREASE_PERIOD_TRANSLATION_PREFIX . 'hourly' => 'hourly',
        self::WAGE_INCREASE_PERIOD_TRANSLATION_PREFIX . 'weekly' => 'weekly',
        self::WAGE_INCREASE_PERIOD_TRANSLATION_PREFIX . 'monthly' => 'monthly',
        self::WAGE_INCREASE_PERIOD_TRANSLATION_PREFIX . 'do-not-know' => 'do-not-know',
        self::WAGE_INCREASE_PERIOD_TRANSLATION_PREFIX . 'other' => 'other',
    ];

    const BONUS_REASON_TRANSLATION_PREFIX = 'domestic.driver-availability.bonuses.reasons-for-bonuses.choices.';
    const BONUS_REASON_CHOICES = [
        self::BONUS_REASON_TRANSLATION_PREFIX . 'recruit' => 'recruit',
        self::BONUS_REASON_TRANSLATION_PREFIX . 'retain' => 'retain',
    ];

    const TRISTATE_TRANSLATION_PREFIX = 'common.choices.boolean.';
    const TRISTATE_CHOICES = [
        self::TRISTATE_TRANSLATION_PREFIX.'yes' => 'yes',
        self::TRISTATE_TRANSLATION_PREFIX.'no' => 'no',
        self::TRISTATE_TRANSLATION_PREFIX.'do-not-know' => 'do-not-know',
    ];

    const MISSING_DELIVERY_TRANSLATION_PREFIX = 'domestic.driver-availability.deliveries.missed-deliveries.';
    const MISSING_DELIVERY_CHOICES = [
        self::MISSING_DELIVERY_TRANSLATION_PREFIX.'no' => 'no',
        self::MISSING_DELIVERY_TRANSLATION_PREFIX.'no-with-workarounds' => 'no-with-workarounds',
        self::MISSING_DELIVERY_TRANSLATION_PREFIX.'yes' => 'yes',
        self::MISSING_DELIVERY_TRANSLATION_PREFIX.'do-not-know' => 'do-not-know',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private string $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\PositiveOrZero(groups={"driver-availability.drivers-and-vacancies"})
     */
    private ?int $numberOfDriversEmployed = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\NotNull(groups={"driver-availability.drivers-and-vacancies"}, message="common.choice.not-null")
     */
    private ?string $hasVacancies = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\PositiveOrZero(groups={"driver-availability.drivers-and-vacancies"})
     */
    private ?int $numberOfDriverVacancies = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $reasonsForDriverVacancies = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"driver-availability.drivers-and-vacancies"})
     */
    private ?string $reasonsForDriverVacanciesOther = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\PositiveOrZero(groups={"driver-availability.drivers-and-vacancies"})
     */
    private ?int $numberOfDriversThatHaveLeft = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $haveWagesIncreased = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\PositiveOrZero(groups={"driver-availability.wages"})
     */
    private ?string $averageWageIncrease = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?string $legacyAverageWageIncreasePercentage = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"driver-availability.wages"})
     */
    private ?string $wageIncreasePeriod = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"driver-availability.wages"})
     */
    private ?string $wageIncreasePeriodOther = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $reasonsForWageIncrease = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"driver-availability.wages"})
     */
    private ?string $reasonsForWageIncreaseOther = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $hasPaidBonus = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\PositiveOrZero(groups={"driver-availability.bonuses"})
     */
    private ?int $averageBonus = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $reasonsForBonuses = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\PositiveOrZero(groups={"driver-availability.deliveries"})
     */
    private ?int $numberOfLorriesOperated = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\PositiveOrZero(groups={"driver-availability.deliveries"})
     */
    private ?int $numberOfParkedLorries = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $hasMissedDeliveries = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\PositiveOrZero(groups={"driver-availability.deliveries"})
     */
    private ?int $numberOfMissedDeliveries = null;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, mappedBy="driverAvailability", cascade={"persist", "remove"})
     */
    private ?Survey $survey = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $exportedDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumberOfDriversEmployed(): ?int
    {
        return $this->numberOfDriversEmployed;
    }

    public function setNumberOfDriversEmployed(?int $numberOfDriversEmployed): self
    {
        $this->numberOfDriversEmployed = $numberOfDriversEmployed;

        return $this;
    }

    public function getHasVacancies(): ?string
    {
        return $this->hasVacancies;
    }

    public function setHasVacancies(?string $hasVacancies): self
    {
        $this->hasVacancies = $hasVacancies;

        return $this;
    }

    public function getNumberOfDriverVacancies(): ?int
    {
        return $this->numberOfDriverVacancies;
    }

    public function setNumberOfDriverVacancies(?int $numberOfDriverVacancies): self
    {
        $this->numberOfDriverVacancies = $numberOfDriverVacancies;

        return $this;
    }

    public function getReasonsForDriverVacancies(): ?array
    {
        return $this->reasonsForDriverVacancies;
    }

    public function setReasonsForDriverVacancies(?array $reasonsForDriverVacancies): self
    {
        $this->reasonsForDriverVacancies = ($reasonsForDriverVacancies === null) ?
            null :
            array_values($reasonsForDriverVacancies);

        return $this;
    }

    public function getNumberOfDriversThatHaveLeft(): ?int
    {
        return $this->numberOfDriversThatHaveLeft;
    }

    public function setNumberOfDriversThatHaveLeft(?int $numberOfDriversThatHaveLeft): self
    {
        $this->numberOfDriversThatHaveLeft = $numberOfDriversThatHaveLeft;

        return $this;
    }

    public function getHaveWagesIncreased(): ?string
    {
        return $this->haveWagesIncreased;
    }

    public function setHaveWagesIncreased(?string $haveWagesIncreased): self
    {
        $this->haveWagesIncreased = $haveWagesIncreased;

        return $this;
    }

    public function getReasonsForWageIncrease(): ?array
    {
        return $this->reasonsForWageIncrease;
    }

    public function setReasonsForWageIncrease(?array $reasonsForWageIncrease): self
    {
        $this->reasonsForWageIncrease = ($reasonsForWageIncrease === null) ?
            null :
            array_values($reasonsForWageIncrease);

        return $this;
    }

    public function getWageIncreasePeriod(): ?string
    {
        return $this->wageIncreasePeriod;
    }

    public function setWageIncreasePeriod(?string $wageIncreasePeriod): self
    {
        $this->wageIncreasePeriod = $wageIncreasePeriod;

        if ($wageIncreasePeriod === 'other') {
            $this->wageIncreasePeriodOther = null;
        }

        return $this;
    }

    public function getWageIncreasePeriodOther(): ?string
    {
        return $this->wageIncreasePeriodOther;
    }

    public function setWageIncreasePeriodOther(?string $wageIncreasePeriodOther): self
    {
        $this->wageIncreasePeriodOther = $wageIncreasePeriodOther;

        return $this;
    }

    public function getHasPaidBonus(): ?string
    {
        return $this->hasPaidBonus;
    }

    public function setHasPaidBonus(?string $hasPaidBonus): self
    {
        $this->hasPaidBonus = $hasPaidBonus;

        return $this;
    }

    public function getAverageBonus(): ?int
    {
        return $this->averageBonus;
    }

    public function setAverageBonus(?int $averageBonus): self
    {
        $this->averageBonus = $averageBonus;

        return $this;
    }

    public function getReasonsForBonuses(): ?array
    {
        return $this->reasonsForBonuses;
    }

    public function setReasonsForBonuses(?array $reasonsForBonuses): self
    {
        $this->reasonsForBonuses = ($reasonsForBonuses === null) ?
            null :
            array_values($reasonsForBonuses);

        return $this;
    }

    public function getNumberOfParkedLorries(): ?int
    {
        return $this->numberOfParkedLorries;
    }

    public function setNumberOfParkedLorries(?int $numberOfParkedLorries): self
    {
        $this->numberOfParkedLorries = $numberOfParkedLorries;

        return $this;
    }

    public function getNumberOfMissedDeliveries(): ?int
    {
        return $this->numberOfMissedDeliveries;
    }

    public function setNumberOfMissedDeliveries(?int $numberOfMissedDeliveries): self
    {
        $this->numberOfMissedDeliveries = $numberOfMissedDeliveries;

        return $this;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(?Survey $survey): self
    {
        // unset the owning side of the relation if necessary
        if ($survey === null && $this->survey !== null) {
            $this->survey->setDriverAvailability(null);
        }

        // set the owning side of the relation if necessary
        if ($survey !== null && $survey->getDriverAvailability() !== $this) {
            $survey->setDriverAvailability($this);
        }

        $this->survey = $survey;

        return $this;
    }

    public function getHasMissedDeliveries(): ?string
    {
        return $this->hasMissedDeliveries;
    }

    public function setHasMissedDeliveries(?string $hasMissedDeliveries): self
    {
        $this->hasMissedDeliveries = $hasMissedDeliveries;

        return $this;
    }

    public function getNumberOfLorriesOperated(): ?int
    {
        return $this->numberOfLorriesOperated;
    }

    public function setNumberOfLorriesOperated(?int $numberOfLorriesOperated): self
    {
        $this->numberOfLorriesOperated = $numberOfLorriesOperated;

        return $this;
    }

    public function getReasonsForDriverVacanciesOther(): ?string
    {
        return $this->reasonsForDriverVacanciesOther;
    }

    public function setReasonsForDriverVacanciesOther(?string $reasonsForDriverVacanciesOther): self
    {
        $this->reasonsForDriverVacanciesOther = $reasonsForDriverVacanciesOther;

        return $this;
    }

    public function getReasonsForWageIncreaseOther(): ?string
    {
        return $this->reasonsForWageIncreaseOther;
    }

    public function setReasonsForWageIncreaseOther(?string $reasonsForWageIncreaseOther): self
    {
        $this->reasonsForWageIncreaseOther = $reasonsForWageIncreaseOther;

        return $this;
    }

    public function getExportedDate(): ?\DateTime
    {
        return $this->exportedDate;
    }

    public function setExportedDate(?\DateTime $exportedDate): self
    {
        $this->exportedDate = $exportedDate;

        return $this;
    }

    public function getAverageWageIncrease(): ?string
    {
        return $this->averageWageIncrease;
    }

    public function setAverageWageIncrease(?string $averageWageIncrease): self
    {
        $this->averageWageIncrease = $averageWageIncrease;
        return $this;
    }

    public function getLegacyAverageWageIncreasePercentage(): ?string
    {
        return $this->legacyAverageWageIncreasePercentage;
    }

    public function setLegacyAverageWageIncreasePercentage(?string $legacyAverageWageIncreasePercentage): self
    {
        $this->legacyAverageWageIncreasePercentage = $legacyAverageWageIncreasePercentage;
        return $this;
    }
}
