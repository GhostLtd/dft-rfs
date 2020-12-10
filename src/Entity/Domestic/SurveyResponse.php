<?php

namespace App\Entity\Domestic;

use App\Entity\Address;
use App\Entity\SurveyResponseTrait;
use App\Repository\Domestic\SurveyResponseRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\Validator as AppAssert;

/**
 * @ORM\Entity(repositoryClass=SurveyResponseRepository::class)
 * @ORM\Table("domestic_survey_response")
 */
class SurveyResponse
{
    const IN_POSSESSION_YES = 'yes';
    const IN_POSSESSION_SCRAPPED_OR_STOLEN = 'scrapped-or-stolen';
    const IN_POSSESSION_SOLD = 'sold';
    const IN_POSSESSION_ON_HIRE = 'on-hire';

    const REASON_NOT_TAXED = 'not-taxed';
    const REASON_NO_WORK = 'no-work';
    const REASON_REPAIR = 'repair';
    const REASON_SITE_WORK_ONLY = 'site-work-only';
    const REASON_HOLIDAY = 'holiday';
    const REASON_MAINTENANCE = 'maintenance';
    const REASON_NO_DRIVER = 'no-driver';
    const REASON_OTHER = 'other';

    const IN_POSSESSION_TRANSLATION_PREFIX = 'domestic.survey-response.in-possession-of-vehicle.option.';

    const IN_POSSESSION_CHOICES = [
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_YES => self::IN_POSSESSION_YES,
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_ON_HIRE => self::IN_POSSESSION_ON_HIRE,
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_SCRAPPED_OR_STOLEN => self::IN_POSSESSION_SCRAPPED_OR_STOLEN,
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_SOLD => self::IN_POSSESSION_SOLD,
    ];

    const EMPTY_SURVEY_REASON_TRANSLATION_PREFIX = 'domestic.survey-response.reason-for-empty-survey.option.';
    const EMPTY_SURVEY_REASON_CHOICES = [
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NOT_TAXED => self::REASON_NOT_TAXED,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NO_WORK => self::REASON_NO_WORK,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_REPAIR => self::REASON_REPAIR,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_SITE_WORK_ONLY => self::REASON_SITE_WORK_ONLY,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_HOLIDAY => self::REASON_HOLIDAY,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_MAINTENANCE => self::REASON_MAINTENANCE,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NO_DRIVER => self::REASON_NO_DRIVER,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_OTHER => self::REASON_OTHER,
    ];

    // https://www.ons.gov.uk/businessindustryandtrade/business/activitysizeandlocation/adhocs/007855enterprisesintheunitedkingdombyemployeesizeband
    // https://www.thecompanywarehouse.co.uk/blog/what-is-an-sme
    const EMPLOYEES_1_TO_9 = '1-9';
    const EMPLOYEES_10_TO_49 = '10-49';
    const EMPLOYEES_50_TO_249 = '50-249';
    const EMPLOYEES_250_TO_499 = '250-499';
    const EMPLOYEES_500_TO_10000 = '500-10000';
    const EMPLOYEES_10001_TO_30000 = '10001-30000';
    const EMPLOYEES_MORE_THAN_30000 = '>30000';

    const EMPLOYEES_TRANSLATION_PREFIX = 'domestic.number-of-employees.';
    const EMPLOYEES_CHOICES = [
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_1_TO_9 => self::EMPLOYEES_1_TO_9,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_10_TO_49 => self::EMPLOYEES_10_TO_49,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_50_TO_249 => self::EMPLOYEES_50_TO_249,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_250_TO_499 => self::EMPLOYEES_250_TO_499,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_500_TO_10000 => self::EMPLOYEES_500_TO_10000,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_10001_TO_30000 => self::EMPLOYEES_10001_TO_30000,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_MORE_THAN_30000 => self::EMPLOYEES_MORE_THAN_30000,
    ];

    use SurveyResponseTrait;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\NotNull(message="common.choice.not-null", groups={"business_details"})
     */
    private $numberOfEmployees;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="common.survey-response.name.not-blank", groups={"hiree_details"})
     */
    private $hireeName;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="common.email.not-blank", groups={"hiree_details"})
     * @Assert\Email(message="common.email.invalid", groups={"hiree_details"})
     */
    private $hireeEmail;

    /**
     * @ORM\Embedded(class=Address::class)
     * @AppAssert\ValidAddress(groups={"hiree_details"})
     */
    private $hireeAddress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="common.survey-response.name.not-blank", groups={"sold_details"})
     */
    private $newOwnerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="common.email.not-blank", groups={"sold_details"})
     * @Assert\Email(message="common.email.invalid", groups={"sold_details"})
     */
    private $newOwnerEmail;

    /**
     * @ORM\Embedded(class=Address::class)
     * @AppAssert\ValidAddress(groups={"sold_details"})
     */
    private $newOwnerAddress;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\NotNull(message="common.date.not-null", groups={"sold_details", "scrapped_details"})
     */
    private $unableToCompleteDate;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Assert\NotNull(message="common.choice.not-null", groups={"domestic.in-possession"})
     */
    private $isInPossessionOfVehicle;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Assert\NotNull(message="common.choice.not-null", groups={"reason_for_empty_survey"})
     */
    private $reasonForEmptySurvey;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, inversedBy="response")
     * @ORM\JoinColumn(nullable=false)
     */
    private $survey;

    /**
     * @ORM\OneToOne(targetEntity=Vehicle::class, cascade={"persist"})
     * @Assert\Valid(groups={
     *     "vehicle_axle_configuration",
     *     "vehicle_body_type",
     *     "vehicle_fuel_quantity",
     *     "vehicle_trailer_configuration",
     *     "vehicle_weight",
     *     "vehicle_operation_type",
     * })
     */
    private $vehicle;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $actualVehicleLocation;

    /**
     * @var Day[]
     * @ORM\OneToMany(targetEntity=Day::class, mappedBy="response", orphanRemoval=true, indexBy="number")
     * @ORM\OrderBy({"number" = "ASC"})
     */
    private $days;

    public function __construct()
    {
        $this->days = new ArrayCollection();
    }

    public function getNumberOfEmployees(): ?string
    {
        return $this->numberOfEmployees;
    }

    public function setNumberOfEmployees(?string $numberOfEmployees): self
    {
        $this->numberOfEmployees = $numberOfEmployees;

        return $this;
    }

    public function getHireeName(): ?string
    {
        return $this->hireeName;
    }

    public function setHireeName(?string $hireeName): self
    {
        $this->hireeName = $hireeName;

        return $this;
    }

    public function getHireeEmail(): ?string
    {
        return $this->hireeEmail;
    }

    public function setHireeEmail(?string $hireeEmail): self
    {
        $this->hireeEmail = $hireeEmail;

        return $this;
    }

    public function getHireeAddress(): ?Address
    {
        return $this->hireeAddress;
    }

    public function setHireeAddress(?Address $hireeAddress): self
    {
        $this->hireeAddress = $hireeAddress;

        return $this;
    }

    public function getNewOwnerAddress(): ?Address
    {
        return $this->newOwnerAddress;
    }

    public function setNewOwnerAddress(?Address $newOwnerAddress): self
    {
        $this->newOwnerAddress = $newOwnerAddress;

        return $this;
    }

    public function getUnableToCompleteDate(): ?DateTimeInterface
    {
        return $this->unableToCompleteDate;
    }

    public function setUnableToCompleteDate(?DateTimeInterface $unableToCompleteDate): self
    {
        $this->unableToCompleteDate = $unableToCompleteDate;

        return $this;
    }

    public function getIsInPossessionOfVehicle(): ?string
    {
        return $this->isInPossessionOfVehicle;
    }

    public function setIsInPossessionOfVehicle(?string $isInPossessionOfVehicle): self
    {
        if ($this->isInPossessionOfVehicle !== $isInPossessionOfVehicle) {
            $this
                ->setUnableToCompleteDate(null)
                ->setNewOwnerName(null)
                ->setNewOwnerEmail(null)
                ->setNewOwnerAddress(null)
                ->setHireeName(null)
                ->setHireeEmail(null)
                ->setHireeAddress(null)
            ;
        }
        $this->isInPossessionOfVehicle = $isInPossessionOfVehicle;

        return $this;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(Survey $survey): self
    {
        $this->survey = $survey;

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

    public function getActualVehicleLocation(): ?string
    {
        return $this->actualVehicleLocation;
    }

    public function setActualVehicleLocation(?string $actualVehicleLocation): self
    {
        $this->actualVehicleLocation = $actualVehicleLocation;

        return $this;
    }

    public function getNewOwnerName(): ?string
    {
        return $this->newOwnerName;
    }

    public function setNewOwnerName(?string $newOwnerName): self
    {
        $this->newOwnerName = $newOwnerName;

        return $this;
    }

    public function getNewOwnerEmail(): ?string
    {
        return $this->newOwnerEmail;
    }

    public function setNewOwnerEmail(?string $newOwnerEmail): self
    {
        $this->newOwnerEmail = $newOwnerEmail;

        return $this;
    }

    /**
     * @return Collection|Day[]
     */
    public function getDays(): Collection
    {
        return $this->days;
    }

    public function addDay(Day $day): self
    {
        if (!$this->days->contains($day)) {
            $this->days[] = $day;
            $day->setResponse($this);
        }

        return $this;
    }

    public function removeDay(Day $day): self
    {
        if ($this->days->removeElement($day)) {
            // set the owning side to null (unless already changed)
            if ($day->getResponse() === $this) {
                $day->setResponse(null);
            }
        }

        return $this;
    }

    /**
     * @param $dayNumber
     * @return Day | null
     */
    public function getDayByNumber($dayNumber): ?Day
    {
        foreach ($this->days ?? [] as $day) {
            if ($day->getNumber() === $dayNumber) {
                return $day;
            }
        }
        return null;
    }

    public function getReasonForEmptySurvey()
    {
        return $this->reasonForEmptySurvey;
    }

    public function setReasonForEmptySurvey($reasonForEmptySurvey): self
    {
        $this->reasonForEmptySurvey = $reasonForEmptySurvey;
        return $this;
    }

    public function hasJourneys()
    {
        foreach ($this->getDays() as $day)
        {
            $summaryOrStops = $day->getHasMoreThanFiveStops() ? $day->getSummary() : $day->getStops();
            if (!empty($summaryOrStops)) return true;
        }
        return false;
    }

}
