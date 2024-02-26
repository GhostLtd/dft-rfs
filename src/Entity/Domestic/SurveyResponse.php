<?php

namespace App\Entity\Domestic;

use App\Entity\Address;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use App\Entity\SurveyResponseTrait;
use App\Repository\Domestic\SurveyResponseRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\Validator as AppAssert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table('domestic_survey_response')]
#[ORM\Entity(repositoryClass: SurveyResponseRepository::class)]
class SurveyResponse extends AbstractSurveyResponse
{
    public const IN_POSSESSION_YES = 'yes';
    public const IN_POSSESSION_SCRAPPED_OR_STOLEN = 'scrapped-or-stolen';
    public const IN_POSSESSION_SOLD = 'sold';
    public const IN_POSSESSION_ON_HIRE = 'on-hire';

    public const REASON_NOT_TAXED = 'not-taxed';
    public const REASON_NO_WORK = 'no-work';
    public const REASON_REPAIR = 'repair';
    public const REASON_SITE_WORK_ONLY = 'site-work-only';
    public const REASON_HOLIDAY = 'holiday';
    public const REASON_MAINTENANCE = 'maintenance';
    public const REASON_NO_DRIVER = 'no-driver';
    public const REASON_OTHER = 'other';

    public const IN_POSSESSION_TRANSLATION_PREFIX = 'domestic.survey-response.in-possession-of-vehicle.option.';

    public const IN_POSSESSION_CHOICES = [
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_YES => self::IN_POSSESSION_YES,
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_ON_HIRE => self::IN_POSSESSION_ON_HIRE,
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_SCRAPPED_OR_STOLEN => self::IN_POSSESSION_SCRAPPED_OR_STOLEN,
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_SOLD => self::IN_POSSESSION_SOLD,
    ];

    public const IS_EXEMPT_TRANSLATION_PREFIX = 'domestic.survey-response.is-exempt-vehicle-type.option.';

    public const IS_EXEMPT_YES = 'yes';
    public const IS_EXEMPT_NO = 'no';

    public const IS_EXEMPT_CHOICES = [
        self::IS_EXEMPT_TRANSLATION_PREFIX . self::IS_EXEMPT_YES => true,
        self::IS_EXEMPT_TRANSLATION_PREFIX . self::IS_EXEMPT_NO => false,
    ];

    public const EMPTY_SURVEY_REASON_TRANSLATION_PREFIX = 'domestic.survey-response.reason-for-empty-survey.option.';
    public const EMPTY_SURVEY_REASON_CHOICES = [
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NOT_TAXED => self::REASON_NOT_TAXED,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NO_WORK => self::REASON_NO_WORK,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_REPAIR => self::REASON_REPAIR,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_SITE_WORK_ONLY => self::REASON_SITE_WORK_ONLY,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_HOLIDAY => self::REASON_HOLIDAY,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_MAINTENANCE => self::REASON_MAINTENANCE,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NO_DRIVER => self::REASON_NO_DRIVER,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_OTHER => self::REASON_OTHER,
    ];

    public const EMPTY_SURVEY_REASON_EXPORT_IDS = [
        self::REASON_NOT_TAXED => 7,
        self::REASON_NO_WORK => 4,
        self::REASON_REPAIR => 1,
        self::REASON_SITE_WORK_ONLY => 5,
        self::REASON_HOLIDAY => 2,
        self::REASON_MAINTENANCE => 6,
        self::REASON_NO_DRIVER => 3,
        self::REASON_OTHER => 7,
    ];

    use SurveyResponseTrait;

    public ?int $_summaryCountForExport = null;
    public ?int $_stopCountForExport = null;

    #[Assert\NotBlank(message: 'domestic.survey-response.initial-details.business-name-required', groups: ['contact_details', 'admin_correspondence'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['contact_details', 'admin_correspondence'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contactBusinessName = null;

    #[Assert\NotBlank(message: 'common.survey-response.name.not-blank', groups: ['hiree_details', 'admin_on_hire'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['hiree_details', 'admin_on_hire'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $hireeName = null;


    #[Assert\Email(message: 'common.email.invalid', groups: ['hiree_details', 'admin_on_hire'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['hiree_details', 'admin_on_hire'])]
    #[Assert\Expression('this.getHireeContactMethodCount() > 0', message: 'domestic.survey-response.hiree-details.contact-details-required', groups: ['hiree_details', 'admin_on_hire'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $hireeEmail = null;

    #[Assert\Length(max: 50, maxMessage: 'common.string.max-length', groups: ['hiree_details', 'admin_on_hire'])]
    #[Assert\Expression('this.getHireeContactMethodCount() > 0', message: 'domestic.survey-response.hiree-details.contact-details-required', groups: ['hiree_details', 'admin_on_hire'])]
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $hireeTelephone = null;

    #[Assert\Expression('this.getHireeContactMethodCount() > 0', message: 'domestic.survey-response.hiree-details.contact-details-required', groups: ['hiree_details', 'admin_on_hire'])]
    #[AppAssert\ValidAddress(groups: ["hiree_details", "admin_on_hire"], validatePostcode: true, allowBlank: true)]
    #[ORM\Embedded(class: Address::class)]
    private ?Address $hireeAddress = null;

    #[Assert\NotBlank(message: 'common.survey-response.name.not-blank', groups: ['sold_details', 'admin_sold'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['sold_details', 'admin_sold'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $newOwnerName = null;

    #[Assert\Expression('this.getNewOwnerContactMethodCount() > 0', message: 'domestic.survey-response.sold-details.contact-details-required', groups: ['sold_details', 'admin_sold'])]
    #[Assert\Email(message: 'common.email.invalid', groups: ['sold_details', 'admin_sold'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['sold_details', 'admin_sold'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $newOwnerEmail = null;

    #[Assert\Length(max: 50, maxMessage: 'common.string.max-length', groups: ['sold_details', 'admin_sold'])]
    #[Assert\Expression('this.getNewOwnerContactMethodCount() > 0', message: 'domestic.survey-response.sold-details.contact-details-required', groups: ['sold_details', 'admin_sold'])]
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $newOwnerTelephone = null;

    #[Assert\Expression('this.getNewOwnerContactMethodCount() > 0', message: 'domestic.survey-response.sold-details.contact-details-required', groups: ['sold_details', 'admin_sold'])]
    #[AppAssert\ValidAddress(groups: ["sold_details", "admin_sold"], validatePostcode: true, allowBlank: true)]
    #[ORM\Embedded(class: Address::class)]
    private ?Address $newOwnerAddress = null;

    // N.B. Validation in individual form types or callback validators due to criteria and errors depending upon context.
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTime $unableToCompleteDate = null;

    #[Assert\NotNull(message: 'domestic.survey-response.in-possession-of-vehicle.choice', groups: ['domestic.in-possession'])]
    #[ORM\Column(type: Types::STRING, length: 24, nullable: true)]
    private ?string $isInPossessionOfVehicle = null;

    #[Assert\NotNull(message: 'common.choice.not-null', groups: ['reason_for_empty_survey', 'admin_reason_for_empty_survey'])]
    #[ORM\Column(type: Types::STRING, length: 24, nullable: true)]
    private ?string $reasonForEmptySurvey = null;

    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['reason_for_empty_survey', 'admin_reason_for_empty_survey'])]
    #[Assert\Expression("(this.getReasonForEmptySurvey() != constant('\\\\App\\\\Entity\\\\Domestic\\\\SurveyResponse::REASON_OTHER')) || value != null", message: 'domestic.survey-response.reason-for-empty-survey-other.not-null', groups: ['reason_for_empty_survey', 'admin_reason_for_empty_survey'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $reasonForEmptySurveyOther = null;

    #[Assert\Valid(groups: ['driver-availability.drivers-and-vacancies', 'driver-availability.deliveries', 'driver-availability.wages', 'driver-availability.bonuses'])]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\OneToOne(inversedBy: 'response', targetEntity: Survey::class)]
    private ?Survey $survey = null;

    #[Assert\Valid(groups: ['vehicle_axle_configuration', 'vehicle_body_type', 'vehicle_fuel_quantity', 'vehicle_trailer_configuration', 'vehicle_weight', 'vehicle_operation_type', 'admin_vehicle', 'admin_vehicle_fuel_quantity'])]
    #[ORM\OneToOne(inversedBy: 'response', targetEntity: Vehicle::class, cascade: ['persist'])]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $actualVehicleLocation = null;

    /**
     * @var Collection<Day>
     */
    #[ORM\OneToMany(mappedBy: 'response', targetEntity: Day::class, orphanRemoval: true, indexBy: 'number')]
    #[ORM\OrderBy(['number' => 'ASC'])]
    private Collection $days;

    #[Assert\NotNull(message: 'domestic.survey-response.is-exempt-vehicle-type.choice', groups: ['domestic.is-exempt-vehicle-type', 'admin_exempt_vehicle_type'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $isExemptVehicleType = null;

    #[Assert\Callback(groups: ['admin_sold'])]
    public function validateSoldDateAdmin(ExecutionContextInterface $context): void
    {
        $this->doSoldDateValidation($context, 'soldDate');
    }

    #[Assert\Callback(groups: ['sold_details'])]
    public function validateSoldDateFrontend(ExecutionContextInterface $context): void
    {
        $this->doSoldDateValidation($context, 'unableToCompleteDate');
    }

    #[Assert\Callback(groups: ['admin_scrapped'])]
    public function validateScrappedDateAdmin(ExecutionContextInterface $context): void
    {
        $this->doScrappedDateValidation($context, 'scrappedDate');
    }

    #[Assert\Callback(groups: ['scrapped_details'])]
    public function validateScrappedDateFrontend(ExecutionContextInterface $context): void
    {
        $this->doScrappedDateValidation($context, 'unableToCompleteDate');
    }

    protected function doSoldDateValidation(ExecutionContextInterface $context, string $validationPath): void
    {
        $this->doSoldOrScrappedDateValidation($context, $validationPath, self::IN_POSSESSION_SOLD, "domestic.survey-response.sold-details");
    }

    protected function doScrappedDateValidation(ExecutionContextInterface $context, string $validationPath): void
    {
        $this->doSoldOrScrappedDateValidation($context, $validationPath, self::IN_POSSESSION_SCRAPPED_OR_STOLEN, "domestic.survey-response.scrapped-details");
    }

    protected function doSoldOrScrappedDateValidation(ExecutionContextInterface $context, string $validationPath, string $isPossessionState, string $errorPrefix): void
    {
        /** @var SurveyResponse $response */
        $response = $context->getValue();
        $survey = $response->getSurvey();

        if ($response->getIsInPossessionOfVehicle() === $isPossessionState) {
            $date = $response->getUnableToCompleteDate();
            $max = $survey->getSurveyPeriodEnd();

            if (!$date) {
                $context->buildViolation("{$errorPrefix}.date")->atPath($validationPath)->addViolation();
            } else if ($date > $max) {
                $context->buildViolation("{$errorPrefix}.date-max")->atPath($validationPath)->addViolation();
            }
        }
    }

    public function __construct()
    {
        $this->days = new ArrayCollection();
        $this->isExemptVehicleType = null;
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

    public function getUnableToCompleteDate(): ?DateTime
    {
        return $this->unableToCompleteDate;
    }

    public function setUnableToCompleteDate(?DateTime $unableToCompleteDate): self
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
                ->setHireeAddress(null);
        }

        if ($isInPossessionOfVehicle !== SurveyResponse::IN_POSSESSION_YES) {
            $this->setIsExemptVehicleType(null);
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
     * @return Collection<Day>
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

    public function getDayByNumber(int $dayNumber): ?Day
    {
        foreach ($this->days ?? [] as $day) {
            if ($day->getNumber() === $dayNumber) {
                return $day;
            }
        }

        return null;
    }

    public function getReasonForEmptySurvey(): ?string
    {
        return $this->reasonForEmptySurvey;
    }

    public function setReasonForEmptySurvey($reasonForEmptySurvey): self
    {
        $this->reasonForEmptySurvey = $reasonForEmptySurvey;
        return $this;
    }

    public function getReasonForEmptySurveyExportId(): ?int
    {
        return self::EMPTY_SURVEY_REASON_EXPORT_IDS[$this->reasonForEmptySurvey] ?? null;
    }

    public function getReasonForEmptySurveyOther(): ?string
    {
        return $this->reasonForEmptySurveyOther;
    }

    public function setReasonForEmptySurveyOther(?string $reasonForEmptySurveyOther): self
    {
        $this->reasonForEmptySurveyOther = $reasonForEmptySurveyOther;
        return $this;
    }

    public function getIsExemptVehicleType(): ?bool
    {
        return $this->isExemptVehicleType;
    }

    public function setIsExemptVehicleType(?bool $isExemptVehicleType): self
    {
        $this->isExemptVehicleType = $isExemptVehicleType;
        return $this;
    }

    public function hasJourneys(): bool
    {
        if (isset($this->_stopCountForExport) && isset($this->_summaryCountForExport)) {
            return ($this->_stopCountForExport !== 0 || $this->_summaryCountForExport !== 0);
        }

        foreach ($this->getDays() as $day)
        {
            $summaryOrStops = $day->getHasMoreThanFiveStops() ? $day->getSummary() : $day->getStops();
            if (!empty($summaryOrStops)) return true;
        }
        return false;
    }

    public function mergeInitialDetails(SurveyResponse $response): self
    {
        $this->setContactName($response->getContactName());
        $this->setContactEmail($response->getContactEmail());
        $this->setContactTelephone($response->getContactTelephone());
        $this->setContactBusinessName($response->getContactBusinessName());

        $this->setIsInPossessionOfVehicle($response->getIsInPossessionOfVehicle());

        $this->setHireeAddress($response->getHireeAddress());
        $this->setHireeEmail($response->getHireeEmail());
        $this->setHireeName($response->getHireeName());

        $this->setNewOwnerAddress($response->getNewOwnerAddress());
        $this->setNewOwnerEmail($response->getNewOwnerEmail());
        $this->setNewOwnerName($response->getNewOwnerName());

        $this->setUnableToCompleteDate($response->getUnableToCompleteDate());

        return $this;
    }

    public function getSummaryDayCount(): int
    {
        return $this->days->filter(fn(Day $day) => $day->getSummary() !== null)->count();
    }

    public function getStopDayCount(): int
    {
        return $this->days->filter(fn(Day $day) => $day->getStops()->count() !== 0)->count();
    }

    public function getTotalDayCount(): int
    {
        return $this->getSummaryDayCount() + $this->getStopDayCount();
    }

    public function getHireeTelephone(): ?string
    {
        return $this->hireeTelephone;
    }

    public function setHireeTelephone(?string $hireeTelephone): self
    {
        $this->hireeTelephone = $hireeTelephone;
        return $this;
    }

    public function getNewOwnerTelephone(): ?string
    {
        return $this->newOwnerTelephone;
    }

    public function setNewOwnerTelephone(?string $newOwnerTelephone): self
    {
        $this->newOwnerTelephone = $newOwnerTelephone;
        return $this;
    }

    /**
     * Used in constraint validation for hiree details
     */
    public function getHireeContactMethodCount(): int
    {
        return
            (empty($this->getHireeEmail()) ? 0 : 1)
            + (empty($this->getHireeTelephone()) ? 0 : 1)
            + ((is_null($this->getHireeAddress()) || !$this->getHireeAddress()->isFilled()) ? 0 : 1)
        ;
    }

    /**
     * Used in constraint validation for sold details
     */
    public function getNewOwnerContactMethodCount(): int
    {
        return
            (empty($this->getNewOwnerEmail()) ? 0 : 1)
            + (empty($this->getNewOwnerTelephone()) ? 0 : 1)
            + ((is_null($this->getNewOwnerAddress()) || !$this->getNewOwnerAddress()->isFilled()) ? 0 : 1)
            ;
    }

    public function getContactBusinessName(): ?string
    {
        return $this->contactBusinessName;
    }

    public function setContactBusinessName(?string $contactBusinessName): self
    {
        $this->contactBusinessName = $contactBusinessName;
        return $this;
    }

    // -----

    public function isSoldScrappedStolenOrOnHire(): bool
    {
        return $this->isInPossessionOfVehicle !== null &&
            in_array($this->isInPossessionOfVehicle, [
                self::IN_POSSESSION_ON_HIRE,
                self::IN_POSSESSION_SCRAPPED_OR_STOLEN,
                self::IN_POSSESSION_SOLD,
            ]);
    }

    public function mergeVehicleFuel(?Vehicle $vehicleToMerge): Vehicle
    {
        $this->vehicle->setFuelQuantity($vehicleToMerge->getFuelQuantity());
        return $this->vehicle;
    }

    public function mergeReasonForEmptySurvey(SurveyResponse $responseToMerge): self
    {
        $this->setReasonForEmptySurvey($responseToMerge->getReasonForEmptySurvey());
        $this->setReasonForEmptySurveyOther($responseToMerge->getReasonForEmptySurveyOther());
        return $this;
    }

    public function mergeVehicleAndBusinessDetails(SurveyResponse $responseToMerge): self
    {
        $this->setNumberOfEmployees($responseToMerge->getNumberOfEmployees());
        $this->setBusinessNature($responseToMerge->getBusinessNature());

        $vehicleToMerge = $responseToMerge->getVehicle();
        $this->vehicle->setOperationType($vehicleToMerge->getOperationType());
        $this->vehicle->setGrossWeight($vehicleToMerge->getGrossWeight());
        $this->vehicle->setCarryingCapacity($vehicleToMerge->getCarryingCapacity());
        $this->vehicle->setAxleConfiguration($vehicleToMerge->getAxleConfiguration());
        $this->vehicle->setBodyType($vehicleToMerge->getBodyType());

        // N.B. Order is important, as setAxleConfiguration deems fit to set trailerConfig to null if axleConfig is null
        $this->vehicle->setTrailerConfiguration($vehicleToMerge->getTrailerConfiguration());
        return $this;
    }

    public function isInPossessionButHasNotCompletedAllDays(): bool
    {
        return
            $this->isInPossessionOfVehicle === self::IN_POSSESSION_YES &&
            $this->getDays()->count() !== 7;
    }

    public function isInPossessionAndHasCompletedAllDays(): bool
    {
        return
            $this->isInPossessionOfVehicle === self::IN_POSSESSION_YES &&
            $this->getDays()->count() === 7;
    }

    public function isInPossessionButIsEarlierThanSurveyPeriodEnd(): bool
    {
        $now = new \DateTime();
        return
            $this->isInPossessionOfVehicle === self::IN_POSSESSION_YES &&
            $now < $this->getSurvey()->getSurveyPeriodEnd();
    }
}
