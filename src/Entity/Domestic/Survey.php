<?php

namespace App\Entity\Domestic;

use App\Entity\BlameLoggable;
use App\Entity\PasscodeUser;
use App\Entity\SurveyInterface;
use App\Entity\SurveyTrait;
use App\Repository\Domestic\SurveyRepository;
use App\Form\Validator as AppAssert;
use App\Utility\RegistrationMarkHelper;
use App\Utility\WeekNumberHelper;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=SurveyRepository::class)
 * @ORM\Table("domestic_survey")
 *
 * @AppAssert\ValidRegistration(groups={"add_survey"})
 */
class Survey implements BlameLoggable, SurveyInterface
{
    use SurveyTrait;

    const STATE_NEW = 'new';
    const STATE_INVITED_USER = 'invited';
    const STATE_IN_PROGRESS = 'in-progress';
    const STATE_CLOSED = 'closed';
    const STATE_APPROVED = 'approved';
    const STATE_REJECTED = 'rejected';
    const STATE_EXPORTING = 'exporting';
    const STATE_EXPORTED = 'exported';

    const STATE_CHOICES = [
        'admin.domestic.survey.state.new' => self::STATE_NEW,
        'admin.domestic.survey.state.invited' => self::STATE_INVITED_USER,
        'admin.domestic.survey.state.in-progress' => self::STATE_IN_PROGRESS,
        'admin.domestic.survey.state.closed' => self::STATE_CLOSED,
        'admin.domestic.survey.state.approved' => self::STATE_APPROVED,
        'admin.domestic.survey.state.rejected' => self::STATE_REJECTED,
        'admin.domestic.survey.state.exporting' => self::STATE_EXPORTING,
        'admin.domestic.survey.state.exported' => self::STATE_EXPORTED,
    ];

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(message="common.choice.not-null", groups={"add_survey"})
     */
    private $isNorthernIreland;

    /**
     * @ORM\OneToOne(targetEntity=SurveyResponse::class, mappedBy="survey", cascade={"persist"})
     */
    private $response;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotBlank(groups={"add_survey"}, message="common.vehicle.vehicle-registration.not-blank")
     */
    private $registrationMark;

    /**
     * @ORM\OneToOne(targetEntity=PasscodeUser::class, mappedBy="domesticSurvey", cascade={"persist", "remove"})
     */
    private $passcodeUser;

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

    public function getBlameLogLabel()
    {
        return "{$this->registrationMark} started "
            . (!is_null($this->getSurveyPeriodStart()) ? $this->getSurveyPeriodStart()->format('Y-m-d') : '[unknown]');
    }

    public function getAssociatedEntityClass()
    {
        return null;
    }

    public function getAssociatedEntityId()
    {
        return null;
    }

    public function getWeekNumberAndYear(): array
    {
        return WeekNumberHelper::getWeekNumberAndYear($this->getSurveyPeriodStart());
    }
}
