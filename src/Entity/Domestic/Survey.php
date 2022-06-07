<?php

namespace App\Entity\Domestic;

use App\Entity\NotifyApiResponse;
use App\Entity\PasscodeUser;
use App\Entity\HaulageSurveyInterface;
use App\Entity\HaulageSurveyTrait;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Repository\Domestic\SurveyRepository;
use App\Form\Validator as AppAssert;
use App\Utility\NotifyApiResponseCleaner;
use App\Utility\RegistrationMarkHelper;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Feedback; // PHPstorm indicates this isn't needed, but it is

/**
 * @ORM\Entity(repositoryClass=SurveyRepository::class)
 * @ORM\Table("domestic_survey")
 *
 * @AppAssert\ValidRegistration(groups={"add_survey", "import_survey"})
 */
class Survey implements HaulageSurveyInterface, ApiResponseInterface
{
    use HaulageSurveyTrait;

    const STATE_REISSUED = 'reissued';

    const STATE_FILTER_CHOICES = [
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

    const REASON_NOT_DELIVERED = 'not-delivered';
    const REASON_REFUSED = 'respondent-refused';
    const REASON_EXCUSED_PERSONAL = 'excused-personal';
    const REASON_EXCUSED_OTHER = 'excused-other';
    const REASON_OTHER = 'other';

    const UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX = 'domestic.survey-response.reason-for-unfilled-survey.option.';
    const UNFILLED_SURVEY_REASON_CHOICES = [
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NOT_DELIVERED => self::REASON_NOT_DELIVERED,
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_REFUSED => self::REASON_REFUSED,
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_EXCUSED_PERSONAL => self::REASON_EXCUSED_PERSONAL,
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_EXCUSED_OTHER => self::REASON_EXCUSED_OTHER,
        self::UNFILLED_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_OTHER => self::REASON_OTHER,

    ];

    const UNFILLED_SURVEY_REASON_EXPORT_IDS = [
        self::REASON_NOT_DELIVERED => 6,
        self::REASON_REFUSED => 7,
        self::REASON_EXCUSED_PERSONAL => 8,
        self::REASON_EXCUSED_OTHER => 9,
        self::REASON_OTHER => 10,
    ];

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(message="common.choice.not-null", groups={"add_survey", "import_survey"})
     */
    private $isNorthernIreland;

    /**
     * @ORM\OneToOne(targetEntity=SurveyResponse::class, mappedBy="survey", cascade={"persist"})
     */
    private $response;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotBlank(groups={"add_survey", "import_survey"}, message="common.vehicle.vehicle-registration.not-blank")
     */
    private $registrationMark;

    /**
     * @ORM\OneToOne(targetEntity=PasscodeUser::class, mappedBy="domesticSurvey", cascade={"persist", "remove"})
     */
    private $passcodeUser;

    /**
     * @ORM\OneToMany(targetEntity=SurveyNote::class, mappedBy="survey")
     * @ORM\OrderBy({"createdAt" = "ASC"})
     */
    private $notes;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, inversedBy="reissuedSurvey", cascade={"persist"}, fetch="EAGER")
     */
    private $originalSurvey;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, mappedBy="originalSurvey", cascade={"persist", "remove"}, fetch="EAGER")
     */
    private $reissuedSurvey;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\NotNull(message="common.choice.not-null", groups={"admin_reason_for_unfilled_survey"})
     */
    private ?string $reasonForUnfilledSurvey;

    /**
     * @ORM\OneToOne(targetEntity=DriverAvailability::class, inversedBy="survey", fetch="EAGER")
     * @Assert\Valid(groups={"driver-availability.drivers-and-vacancies", "driver-availability.deliveries", "driver-availability.wages", "driver-availability.bonuses"})
     */
    private ?DriverAvailability $driverAvailability;

    /**
     * @ORM\ManyToMany(targetEntity=NotifyApiResponse::class)
     * @ORM\JoinTable(
     *     name="domestic_survey_notify_api_responses",
     *     joinColumns={@ORM\JoinColumn(name="survey_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="notify_api_response_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
     * )
     */
    private Collection $apiResponses;

    public function __construct()
    {
        $this->apiResponses = new ArrayCollection();
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

    public function getPasscodeUser(): ?PasscodeUser
    {
        return $this->passcodeUser;
    }

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

    public function getNotes()
    {
        return $this->notes;
    }

    public function addNote(SurveyNote $note): self
    {
        if (!$this->notes->contains($note)) {
            $note->setSurvey($this);
            $this->notes[] = $note;
        }

        return $this;
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

        $isInPossession = $response && $response->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_YES;

        return !$this->isInitialDetailsComplete() ||
            ($isInPossession && !$this->isBusinessAndVehicleDetailsComplete());
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
            !$response->hasJourneys();
    }

    public function clearPersonalData(): self
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

        return $this;
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
}
