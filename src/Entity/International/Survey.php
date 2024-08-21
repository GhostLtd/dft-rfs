<?php

namespace App\Entity\International;

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
use App\Repository\International\SurveyRepository;
use App\Utility\Cleanup\PersonalDataCleanupInterface;
use App\Utility\International\WeekNumberHelper;
use App\Utility\NotifyApiResponseCleaner;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Entity\Feedback; // PHPstorm indicates this isn't needed, but it is

#[ORM\Table(name: 'international_survey')]
#[ORM\Entity(repositoryClass: SurveyRepository::class)]
class Survey implements ApiResponseInterface, HaulageSurveyInterface, PersonalDataCleanupInterface, QualityAssuranceInterface, SurveyManualReminderInterface, SurveyReminderInterface, SurveyStateInterface
{
    use HaulageSurveyTrait;
    use NotifyApiResponseTrait;

    public const STATE_FILTER_CHOICES = [
        self::STATE_NEW,
        self::STATE_INVITATION_PENDING,
        self::STATE_INVITATION_SENT,
        self::STATE_INVITATION_FAILED,
        self::STATE_IN_PROGRESS,
        self::STATE_CLOSED,
        self::STATE_APPROVED,
        self::STATE_REJECTED,
//        self::STATE_EXPORTING,
//        self::STATE_EXPORTED,
    ];

    public const REASON_FOR_EMPTY_SURVEY_NO_INTERNATIONAL_WORK = 'no-international-work';
    public const REASON_FOR_EMPTY_SURVEY_ON_HOLIDAY = 'on-holiday';
    public const REASON_FOR_EMPTY_SURVEY_REPAIR = 'repair';
    public const REASON_FOR_EMPTY_SURVEY_NO_VEHICLES_AVAILABLE = 'no-vehicles';
    public const REASON_FOR_EMPTY_SURVEY_OTHER = 'other';

    public const REASON_FOR_EMPTY_SURVEY_PREFIX = 'international.survey-response.reason-for-empty-survey.';
    public const REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX = self::REASON_FOR_EMPTY_SURVEY_PREFIX . 'choices.';
    public const REASON_FOR_EMPTY_SURVEY_CHOICES = [
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_NO_INTERNATIONAL_WORK => self::REASON_FOR_EMPTY_SURVEY_NO_INTERNATIONAL_WORK,
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_ON_HOLIDAY => self::REASON_FOR_EMPTY_SURVEY_ON_HOLIDAY,
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_REPAIR => self::REASON_FOR_EMPTY_SURVEY_REPAIR,
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_NO_VEHICLES_AVAILABLE => self::REASON_FOR_EMPTY_SURVEY_NO_VEHICLES_AVAILABLE,
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_OTHER => self::REASON_FOR_EMPTY_SURVEY_OTHER,
    ];

    #[Assert\NotBlank(message: 'common.string.not-blank', groups: ['add_survey', 'import_survey'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['add_survey', 'import_survey'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $referenceNumber = null;

    #[Assert\Callback(groups: ['add_survey'])]
    public function validateReferenceNumber(ExecutionContextInterface $context): void
    {
        if (
            $this->referenceNumber &&
            !preg_match('/^\d+\-\d{4}$/', $this->referenceNumber)
        ) {
            $context
                ->buildViolation("international.survey.reference-number.correct-format")
                ->atPath('referenceNumber')
                ->addViolation();
        }
    }

    #[Assert\Valid(groups: ['add_survey', 'import_survey'])]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: Company::class, cascade: ['persist'], inversedBy: 'surveys')]
    private ?Company $company = null;

    #[ORM\OneToOne(targetEntity: SurveyResponse::class, mappedBy: 'survey', cascade: ['persist', 'remove'])]
    private ?SurveyResponse $response = null;

    #[ORM\OneToOne(targetEntity: PasscodeUser::class, mappedBy: 'internationalSurvey', cascade: ['persist', 'remove'], fetch: 'LAZY')]
    private ?PasscodeUser $passcodeUser = null;

    /**
     * @var Collection<SurveyNote>
     */
    #[ORM\OneToMany(targetEntity: SurveyNote::class, mappedBy: 'survey')]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $notes;

    #[Assert\NotNull(message: 'common.choice.invalid', groups: ['reason_for_empty_survey', 'admin_reason_for_empty_survey'])]
    #[ORM\Column(type: Types::STRING, length: 24, nullable: true)]
    private ?string $reasonForEmptySurvey = null;

    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['reason_for_empty_survey', 'admin_reason_for_empty_survey'])]
    #[Assert\Expression("(this.getReasonForEmptySurvey() != constant('\\\\App\\\\Entity\\\\International\\\\Survey::REASON_FOR_EMPTY_SURVEY_OTHER')) || value != null", message: 'international.survey-response.reason-for-empty-survey-other.not-null', groups: ['reason_for_empty_survey', 'admin_reason_for_empty_survey'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $reasonForEmptySurveyOther = null;

    /**
     * @var Collection<int, NotifyApiResponse>
     */
    #[ORM\JoinTable(name: 'international_survey_notify_api_responses')]
    #[ORM\JoinColumn(name: 'survey_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'notify_api_response_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: NotifyApiResponse::class)]
    protected Collection $apiResponses;

    public function __construct()
    {
        $this->apiResponses = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(string $referenceNumber): self
    {
        $this->referenceNumber = $referenceNumber;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;
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
        $newInternationalSurvey = null === $passcodeUser ? null : $this;
        if ($passcodeUser && $passcodeUser->getInternationalSurvey() !== $newInternationalSurvey) {
            $passcodeUser->setInternationalSurvey($newInternationalSurvey);
        }

        return $this;
    }

    public function isInitialDetailsComplete(): bool
    {
        return !!$this->response;
    }

    public function getWeekNumber(): int
    {
        return WeekNumberHelper::getWeekNumber($this->getSurveyPeriodStart());
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

    #[\Override]
    public function getChasedCount(): int
    {
        return array_reduce($this->getNotes()->toArray(), fn($c, NoteInterface $i) => $c += ($i->getWasChased() ? 1 : 0)) ?? 0;
    }

    public function getReasonForEmptySurvey(): ?string
    {
        return $this->reasonForEmptySurvey;
    }

    public function setReasonForEmptySurvey(?string $reasonForEmptySurvey): self
    {
        $this->reasonForEmptySurvey = $reasonForEmptySurvey;
        return $this;
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

    public function shouldAskWhyEmptySurvey(): bool
    {
        $response = $this->getResponse();
        return !$response || (!$response->isNoLongerActive() && $response->getTotalNumberOfTrips() === 0);
    }

    public function mergeClosingDetails(Survey $survey): void
    {
        $this->setReasonForEmptySurvey($survey->getReasonForEmptySurvey());
        $this->setReasonForEmptySurveyOther($survey->getReasonForEmptySurveyOther());
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
                ->setContactEmail(null);
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
                $response->getContactEmail() === null;
        }

        return $isCleared && NotifyApiResponseCleaner::surveyNotifyApiResponsesHaveBeenCleaned($this);
    }

    public function isEarlierThanSurveyPeriodEnd(): bool
    {
        $now = new \DateTime();
        return $now < $this->getSurveyPeriodEnd();
    }

    #[\Override]
    public function getResponseContactEmail(): ?string
    {
        return $this->getResponse()?->getContactEmail();
    }
}
