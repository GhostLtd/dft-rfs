<?php

namespace App\Entity\Domestic;

use App\Entity\CurrencyOrPercentage;
use App\Repository\Domestic\DriverAvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Form\Validator as AppAssert;

/**
 * @ORM\Entity(repositoryClass=DriverAvailabilityRepository::class)
 * @ORM\Table("domestic_driver_availablity")
 */
class DriverAvailability
{
    const VACANCY_REASON_TRANSLATION_PREFIX = 'domestic.driver-availability.drivers.reasons-for-vacancies.choices.';
    const VACANCY_REASON_CHOICES = [
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'lured-away' => 'lured-away',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'covid' => 'covid',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'retirement' => 'retirement',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'new-work' => 'new-work',
//        self::VACANCY_REASON_TRANSLATION_PREFIX . 'no-qual-drivers' => 'no-qual-drivers',
        self::VACANCY_REASON_TRANSLATION_PREFIX . 'ir35-changes' => 'ir35-changes',
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
     */
    private ?int $numberOfDriversEmployed = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $hasVacancies = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $numberOfDriverVacancies = null;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $reasonsForDriverVacancies = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $reasonsForDriverVacanciesOther = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $numberOfDriversThatHaveLeft = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $haveWagesIncreased = null;

    /**
     * @ORM\Embedded(class=CurrencyOrPercentage::class)
     * @AppAssert\ValidValueUnit(groups={"driver-availability.wages"}, allowBlank=true)
     */
    private ?CurrencyOrPercentage $averageWageIncrease = null;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $reasonsForWageIncrease = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $reasonsForWageIncreaseOther = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $hasPaidBonus = null;

    /**
     * @ORM\Embedded(class=CurrencyOrPercentage::class)
     * @AppAssert\ValidValueUnit(groups={"driver-availability.bonuses"}, allowBlank=true)
     */
    private ?CurrencyOrPercentage $averageBonus = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $numberOfLorriesOperated = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $numberOfParkedLorries = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $hasMissedDeliveries = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $numberOfMissedDeliveries = null;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, mappedBy="driverAvailability", cascade={"persist", "remove"})
     */
    private ?Survey $survey = null;

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
        $this->reasonsForDriverVacancies = $reasonsForDriverVacancies;

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

    public function getAverageWageIncrease(): ?CurrencyOrPercentage
    {
        return $this->averageWageIncrease;
    }

    public function setAverageWageIncrease(?CurrencyOrPercentage $averageWageIncrease): self
    {
        $this->averageWageIncrease = $averageWageIncrease;

        return $this;
    }

    public function getReasonsForWageIncrease(): ?array
    {
        return $this->reasonsForWageIncrease;
    }

    public function setReasonsForWageIncrease(?array $reasonsForWageIncrease): self
    {
        $this->reasonsForWageIncrease = $reasonsForWageIncrease;

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

    public function getAverageBonus(): ?CurrencyOrPercentage
    {
        return $this->averageBonus;
    }

    public function setAverageBonus(?CurrencyOrPercentage $averageBonus): self
    {
        $this->averageBonus = $averageBonus;

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
}
