<?php

namespace App\Entity\International;

use App\Entity\PasscodeUser;
use App\Entity\HaulageSurveyInterface;
use App\Entity\HaulageSurveyTrait;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Repository\International\SurveyRepository;
use App\Utility\International\WeekNumberHelper;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=SurveyRepository::class)
 * @ORM\Table(name="international_survey")
 */
class Survey implements HaulageSurveyInterface, ApiResponseInterface
{
    use HaulageSurveyTrait;

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
    ];

    const REASON_FOR_EMPTY_SURVEY_NO_INTERNATIONAL_WORK = 'no-international-work';
    const REASON_FOR_EMPTY_SURVEY_ON_HOLIDAY = 'on-holiday';
    const REASON_FOR_EMPTY_SURVEY_REPAIR = 'repair';
    const REASON_FOR_EMPTY_SURVEY_NO_VEHICLES_AVAILABLE = 'no-vehicles';
    const REASON_FOR_EMPTY_SURVEY_OTHER = 'other';

    const REASON_FOR_EMPTY_SURVEY_PREFIX = 'international.survey-response.reason-for-empty-survey.';
    const REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX = self::REASON_FOR_EMPTY_SURVEY_PREFIX . 'choices.';
    const REASON_FOR_EMPTY_SURVEY_CHOICES = [
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_NO_INTERNATIONAL_WORK => self::REASON_FOR_EMPTY_SURVEY_NO_INTERNATIONAL_WORK,
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_ON_HOLIDAY => self::REASON_FOR_EMPTY_SURVEY_ON_HOLIDAY,
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_REPAIR => self::REASON_FOR_EMPTY_SURVEY_REPAIR,
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_NO_VEHICLES_AVAILABLE => self::REASON_FOR_EMPTY_SURVEY_NO_VEHICLES_AVAILABLE,
        self::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . self::REASON_FOR_EMPTY_SURVEY_OTHER => self::REASON_FOR_EMPTY_SURVEY_OTHER,
    ];

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="common.string.not-blank", groups={"add_survey", "import_survey"})
     * @Assert\Length (max=255, maxMessage="common.string.max-length", groups={"add_survey", "import_survey"})
     */
    private $referenceNumber;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="surveys", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid(groups={"add_survey", "import_survey"})
     */
    private $company;

    /**
     * @ORM\OneToOne(targetEntity=SurveyResponse::class, mappedBy="survey", cascade={"persist"})
     */
    private $response;

    /**
     * @ORM\OneToOne(targetEntity=PasscodeUser::class, mappedBy="internationalSurvey", cascade={"persist", "remove"})
     */
    private $passcodeUser;

    /**
     * @ORM\OneToMany(targetEntity=SurveyNote::class, mappedBy="survey")
     * @ORM\OrderBy({"createdAt" = "ASC"})
     */
    private $notes;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Assert\NotNull(groups={"reason_for_empty_survey", "admin_reason_for_empty_survey"}, message="common.choice.invalid")
     */
    private ?string $reasonForEmptySurvey;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"reason_for_empty_survey", "admin_reason_for_empty_survey"})
     * @Assert\Expression("(this.getReasonForEmptySurvey() != constant('\\App\\Entity\\International\\Survey::REASON_FOR_EMPTY_SURVEY_OTHER')) || value != null", message="international.survey-response.reason-for-empty-survey-other.not-null", groups={"reason_for_empty_survey", "admin_reason_for_empty_survey"})
     */
    private ?string $reasonForEmptySurveyOther;

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

    public function getPasscodeUser(): ?PasscodeUser
    {
        return $this->passcodeUser;
    }

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
        return !$response || ($response->getTotalNumberOfTrips() === 0 && !$response->isNoLongerActive());
    }

    public function mergeClosingDetails(Survey $survey)
    {
        $this->setReasonForEmptySurvey($survey->getReasonForEmptySurvey());
        $this->setReasonForEmptySurveyOther($survey->getReasonForEmptySurveyOther());
    }

    public function clearPersonalData(): self
    {
        $this
            ->setInvitationEmail(null)
            ->setInvitationAddress(null);

        $response = $this->getResponse();
        if ($response) {
            $response
                ->setContactName(null)
                ->setContactTelephone(null)
                ->setContactEmail(null);
        }

        return $this;
    }
}
