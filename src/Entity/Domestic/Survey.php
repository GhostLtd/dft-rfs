<?php

namespace App\Entity\Domestic;

use App\Entity\PasscodeUser;
use App\Entity\HaulageSurveyInterface;
use App\Entity\HaulageSurveyTrait;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Repository\Domestic\SurveyRepository;
use App\Form\Validator as AppAssert;
use App\Utility\RegistrationMarkHelper;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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

    public function isInitialDetailsComplete()
    {
        return (
            !empty($this->getResponse())
        );
    }

    public function isBusinessAndVehicleDetailsComplete()
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
        $newDomesticSurvey = null === $passcodeUser ? null : $this;
        if ($passcodeUser->getDomesticSurvey() !== $newDomesticSurvey) {
            $passcodeUser->setDomesticSurvey($newDomesticSurvey);
        }

        return $this;
    }

    public function getWeekNumberAndYear(): array
    {
        return WeekNumberHelper::getWeekNumberAndYear($this->getSurveyPeriodStart());
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
}
