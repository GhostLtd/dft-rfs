<?php

namespace App\Entity\Domestic;

use App\Entity\NoteInterface;
use App\Entity\NotifyApiResponse;
use App\Entity\NotifyApiResponseTrait;
use App\Entity\PasscodeUser;
use App\Entity\HaulageSurveyInterface;
use App\Entity\HaulageSurveyTrait;
use App\Entity\QualityAssuranceInterface;
use App\Entity\SurveyManualReminderInterface;
use App\Entity\SurveyReminderInterface;
use App\Entity\SurveyStateInterface;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Repository\Domestic\SurveyRepository;
use App\Form\Validator as AppAssert;
use App\Utility\Cleanup\PersonalDataCleanupInterface;
use App\Utility\NotifyApiResponseCleaner;
use App\Utility\RegistrationMarkHelper;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Feedback; // PHPstorm indicates this isn't needed, but it is

#[AppAssert\ValidRegistration(groups: ["add_survey", "import_survey"])]
#[ORM\Table('domestic_survey')]
#[ORM\Entity(repositoryClass: SurveyRepository::class)]
class Survey implements ApiResponseInterface, HaulageSurveyInterface, PersonalDataCleanupInterface, QualityAssuranceInterface, SurveyManualReminderInterface, SurveyReminderInterface, SurveyStateInterface
{
    use HaulageSurveyTrait;
    use NotifyApiResponseTrait;

    public const STATE_REISSUED = 'reissued';

    public const STATE_FILTER_CHOICES = [
        self::STATE_NEW,
        self::STATE_INVITATION_PENDING,
        self::STATE_INVITATION_SENT,
        self::STATE_INVITATION_FAILED,
        self::STATE_IN_PROGRESS,
        self::STATE_CLOSED,
        self::STATE_APPROVED,
        self::STATE_REJECTED,
        self::STATE_EXPORTING,
        self::STATE_EXPORTED,
        self::STATE_REISSUED,
    ];

    public const REASON_NOT_DELIVERED = 'not-delivered';
    public const REASON_REFUSED = 'respondent-refused';
    public const REASON_EXCUSED_PERSONAL = 'excused-personal';
    public const REASON_EXCUSED_OTHER = 'excused-other';
    public const REASON_OTHER = 'other';

    public const UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX = 'domestic.survey-response.reason-for-unfilled-survey.option.';
    public const UNFILLED_SURVEY_REASON_CHOICES = [
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NOT_DELIVERED => self::REASON_NOT_DELIVERED,
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_REFUSED => self::REASON_REFUSED,
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_EXCUSED_PERSONAL => self::REASON_EXCUSED_PERSONAL,
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_EXCUSED_OTHER => self::REASON_EXCUSED_OTHER,
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_OTHER => self::REASON_OTHER,

    ];

    public const UNFILLED_SURVEY_REASON_EXPORT_IDS = [
        self::REASON_NOT_DELIVERED => 6,
        self::REASON_REFUSED => 7,
        self::REASON_EXCUSED_PERSONAL => 8,
        self::REASON_EXCUSED_OTHER => 9,
        self::REASON_OTHER => 10,
    ];

    #[Assert\NotNull(message: 'common.choice.not-null', groups: ['add_survey', 'import_survey'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $isNorthernIreland = null;

    #[ORM\OneToOne(mappedBy: 'survey', targetEntity: SurveyResponse::class, cascade: ['persist'])]
    private ?SurveyResponse $response = null;

    #[Assert\NotBlank(message: 'common.vehicle.vehicle-registration.not-blank', groups: ['add_survey', 'import_survey'])]
    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $registrationMark = null;

    #[ORM\OneToOne(mappedBy: 'domesticSurvey', targetEntity: PasscodeUser::class, cascade: ['persist', 'remove'])]
    private ?PasscodeUser $passcodeUser = null;

    /**
     * @var Collection<SurveyNote>
     */
    #[ORM\OneToMany(targetEntity: SurveyNote::class, mappedBy: 'survey')]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $notes;

    #[ORM\OneToOne(inversedBy: 'reissuedSurvey', targetEntity: Survey::class, cascade: ['persist'], fetch: 'EAGER')]
    private ?Survey $originalSurvey = null;

    #[ORM\OneToOne(mappedBy: 'originalSurvey', targetEntity: Survey::class, cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private ?Survey $reissuedSurvey = null;

    #[Assert\NotNull(message: 'common.choice.not-null', groups: ['admin_reason_for_unfilled_survey'])]
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $reasonForUnfilledSurvey = null;

    #[Assert\Valid(groups: ['driver-availability.drivers-and-vacancies', 'driver-availability.deliveries', 'driver-availability.wages', 'driver-availability.bonuses'])]
    #[ORM\OneToOne(inversedBy: 'survey', targetEntity: DriverAvailability::class, fetch: 'EAGER')]
    private ?DriverAvailability $driverAvailability = null;

    /**
     * @var Collection<int, NotifyApiResponse>
     */
    #[ORM\JoinTable(name: 'domestic_survey_notify_api_responses')]
    #[ORM\JoinColumn(name: 'survey_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'notify_api_response_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: NotifyApiResponse::class)]
    protected Collection $apiResponses;

    public function __construct()
    {
        $this->apiResponses = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }

    public function isInitialDetailsComplete(): bool
    {
        return (
            !empty($this->getResponse())
        );
    }

    public function isBusinessAndVehicleDetailsComplete(): bool
    {
        return (
            $this->isInitialDetailsComplete()
            && !empty($this->getResponse()->getBusinessNature())
            && !empty($this->getResponse()->getVehicle()->getGrossWeight())
            && !empty($this->getResponse()->getVehicle()->getCarryingCapacity())
            && !empty($this->getResponse()->getVehicle()->getAxleConfiguration())
        );
    }

    public function getReferenceNumber(): string
    {
        [$week, $year] = $this->getWeekNumberAndYear();
        $reissue = $this->getOriginalSurvey() ? '-R' : '';
        $region = $this->getIsNorthernIreland() ? 'NI' : 'GB';
        return "{$this->getRegistrationMark()}-{$year}{$reissue}-{$region}";
    }

    public function isInActiveState(): bool
    {
        return in_array($this->state, self::ACTIVE_STATES);
    }

    public function getIsNorthernIreland(): ?bool
    {
        return $this->isNorthernIreland;
    }

    public function setIsNorthernIreland(bool $isNorthernIreland): self
    {
        $this->isNorthernIreland = $isNorthernIreland;
        return $this;
    }

    public function getResponse(): ?SurveyResponse
    {
        return $this->response;
    }

    public function setResponse(SurveyResponse $response): self
    {
        $this->response = $response;

        // set the owning side of the relation if necessary
        if ($response->getSurvey() !== $this) {
            $response->setSurvey($this);
        }

        return $this;
    }

    public function getRegistrationMark(): ?string
    {
        return $this->registrationMark;
    }

    public function setRegistrationMark(string $registrationMark): self
    {
        $helper = new RegistrationMarkHelper($registrationMark);
        $this->registrationMark = $helper->getRegistrationMark();

        return $this;
    }

    #[\Override]
    public function getPasscodeUser(): ?PasscodeUser
    {
        return $this->passcodeUser;
    }

    #[\Override]
    public function setPasscodeUser(?PasscodeUser $passcodeUser): self
    {
        $this->passcodeUser = $passcodeUser;

        // set (or unset) the owning side of the relation if necessary
        if ($passcodeUser && $passcodeUser->getDomesticSurvey() !== $this) {
            $passcodeUser->setDomesticSurvey($this);
        }

        return $this;
    }

    public function getWeekNumberAndYear(): array
    {
        return WeekNumberHelper::getYearlyWeekNumberAndYear($this->getSurveyPeriodStart());
    }

    #[\Override]
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    #[\Override]
    public function setNotes(Collection $notes): Survey
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

    #[\Override]
    public function getChasedCount(): int
    {
        return array_reduce($this->getNotes()->toArray(), fn($c, NoteInterface $i) => $c += ($i->getWasChased() ? 1 : 0)) ?? 0;
    }

    public function getDriverAvailability(): ?DriverAvailability
    {
        return $this->driverAvailability;
    }

    public function setDriverAvailability(?DriverAvailability $driverAvailability): self
    {
        $this->driverAvailability = $driverAvailability;
        return $this;
    }

    public function getOriginalSurvey(): ?self
    {
        return $this->originalSurvey;
    }

    public function setOriginalSurvey(?self $originalSurvey): self
    {
        $this->originalSurvey = $originalSurvey;
        return $this;
    }

    public function getReissuedSurvey(): ?self
    {
        return $this->reissuedSurvey;
    }

    public function setReissuedSurvey(?self $reissuedSurvey): self
    {
        // unset the owning side of the relation if necessary
        if ($reissuedSurvey === null && $this->reissuedSurvey !== null) {
            $this->reissuedSurvey->setOriginalSurvey(null);
        }

        // set the owning side of the relation if necessary
        if ($reissuedSurvey !== null && $reissuedSurvey->getOriginalSurvey() !== $this) {
            $reissuedSurvey->setOriginalSurvey($this);
        }

        $this->reissuedSurvey = $reissuedSurvey;
        return $this;
    }

    public function getReasonForUnfilledSurvey(): ?string
    {
        return $this->reasonForUnfilledSurvey;
    }

    public function setReasonForUnfilledSurvey(?string $reasonForUnfilledSurvey): self
    {
        $this->reasonForUnfilledSurvey = $reasonForUnfilledSurvey;
        return $this;
    }

    public function getReasonForUnfilledSurveyExportId(): ?int
    {
        return self::UNFILLED_SURVEY_REASON_EXPORT_IDS[$this->reasonForUnfilledSurvey] ?? null;
    }

    /**
     * Is the survey completely unfilled?
     *
     * Respondent either:
     * a) Hasn't filled out initial details
     * b) Has stated they're in possession of the vehicle, but hasn't completed business/vehicle details
     */
    public function shouldAskWhyUnfilled(): bool
    {
        $response = $this->getResponse();

        $isInPossessionOfNonExemptVehicle = $response &&
            $response->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_YES &&
            !$response->getIsExemptVehicleType();

        return !$this->isInitialDetailsComplete() ||
            ($isInPossessionOfNonExemptVehicle && !$this->isBusinessAndVehicleDetailsComplete());
    }

    /**
     * Is the survey lacking journeys? (excluding if sold/scrapped/on hire)
     */
    public function shouldAskWhyNoJourneys(): bool
    {
        $response = $this->getResponse();

        return $this->isBusinessAndVehicleDetailsComplete() &&
            $response &&
            $response->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_YES &&
            !$response->getIsExemptVehicleType() &&
            !$response->hasJourneys();
    }

    public function clearPersonalData(): void
    {
        $this
            ->setInvitationEmails(null)
            ->setInvitationAddress(null);

        $response = $this->getResponse();
        if ($response) {
            $response
                ->setContactName(null)
                ->setContactTelephone(null)
                ->setContactEmail(null)
                ->setHireeName(null)
                ->setHireeTelephone(null)
                ->setHireeEmail(null)
                ->setHireeAddress(null)
                ->setNewOwnerName(null)
                ->setNewOwnerTelephone(null)
                ->setNewOwnerEmail(null)
                ->setNewOwnerAddress(null);
        }

        NotifyApiResponseCleaner::cleanSurveyNotifyApiResponses($this);
    }

    public function isPersonalDataCleared(): bool
    {
        $isCleared = $this->invitationEmails === null && $this->invitationAddress === null;

        $response = $this->getResponse();
        if ($response) {
            $isCleared = $isCleared &&
                $response->getContactName() === null &&
                $response->getContactTelephone() === null &&
                $response->getContactEmail() === null &&
                $response->getHireeName() === null &&
                $response->getHireeTelephone() === null &&
                $response->getHireeEmail() === null &&
                $response->getHireeAddress() === null &&
                $response->getNewOwnerName() === null &&
                $response->getNewOwnerTelephone() === null &&
                $response->getNewOwnerEmail() === null &&
                $response->getNewOwnerAddress() === null;
        }

        return $isCleared && NotifyApiResponseCleaner::surveyNotifyApiResponsesHaveBeenCleaned($this);
    }

    public function mergeDriverAvailability(?DriverAvailability $driverAvailabilityToMerge): DriverAvailability
    {
        if ($this->driverAvailability === null || $this->driverAvailability->getId() === null) {
            $this->driverAvailability = new DriverAvailability();
        }

        if ($driverAvailabilityToMerge !== null) {
            $this->driverAvailability
                // BonusesType
                ->setHasPaidBonus($driverAvailabilityToMerge->getHasPaidBonus())
                ->setReasonsForBonuses($driverAvailabilityToMerge->getReasonsForBonuses())
                ->setAverageBonus($driverAvailabilityToMerge->getAverageBonus())
                // DeliveriesType
                ->setNumberOfLorriesOperated($driverAvailabilityToMerge->getNumberOfLorriesOperated())
                ->setNumberOfParkedLorries($driverAvailabilityToMerge->getNumberOfParkedLorries())
                ->setHasMissedDeliveries($driverAvailabilityToMerge->getHasMissedDeliveries())
                ->setNumberOfMissedDeliveries($driverAvailabilityToMerge->getNumberOfMissedDeliveries())
                // DriversAndVacanciesType
                ->setNumberOfDriversEmployed($driverAvailabilityToMerge->getNumberOfDriversEmployed())
                ->setHasVacancies($driverAvailabilityToMerge->getHasVacancies())
                ->setNumberOfDriversThatHaveLeft($driverAvailabilityToMerge->getNumberOfDriversThatHaveLeft())
                ->setNumberOfDriverVacancies($driverAvailabilityToMerge->getNumberOfDriverVacancies())
                ->setReasonsForDriverVacancies($driverAvailabilityToMerge->getReasonsForDriverVacancies())
                ->setReasonsForDriverVacanciesOther($driverAvailabilityToMerge->getReasonsForDriverVacanciesOther())
                // WagesType
                ->setHaveWagesIncreased($driverAvailabilityToMerge->getHaveWagesIncreased())
                ->setAverageWageIncrease($driverAvailabilityToMerge->getAverageWageIncrease())
                ->setWageIncreasePeriod($driverAvailabilityToMerge->getWageIncreasePeriod())
                ->setWageIncreasePeriodOther($driverAvailabilityToMerge->getWageIncreasePeriodOther())
                ->setReasonsForWageIncrease($driverAvailabilityToMerge->getReasonsForWageIncrease())
                ->setReasonsForWageIncreaseOther($driverAvailabilityToMerge->getReasonsForWageIncreaseOther());
        }

        $this->driverAvailability->setSurvey($this);
        return $this->driverAvailability;
    }

    #[\Override]
    public function getResponseContactEmail(): ?string
    {
        return $this->getResponse()?->getContactEmail();
    }

    public function getCompanyNameFromInvitationAddress(): ?string
    {
        return $this->getInvitationAddress()?->getLine1();
    }
}
