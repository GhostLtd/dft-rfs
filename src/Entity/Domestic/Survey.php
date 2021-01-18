<?php

namespace App\Entity\Domestic;

use App\Entity\Address;
use App\Entity\BlameLoggable;
use App\Entity\LongAddress;
use App\Entity\PasscodeUser;
use App\Entity\SurveyTrait;
use App\Repository\Domestic\SurveyRepository;
use App\Form\Validator as AppAssert;
use App\Utility\RegistrationMarkHelper;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=SurveyRepository::class)
 * @ORM\Table("domestic_survey")
 *
 * @AppAssert\ValidRegistration(groups={"add_survey"})
 */
class Survey implements BlameLoggable
{
    use SurveyTrait;

    const STATE_NEW = 'new';
    const STATE_INVITED_USER = 'invited';
    const STATE_REMINDED_USER = 'reminded';
    const STATE_IN_PROGRESS = 'in-progress';
    const STATE_CLOSED = 'closed';
    const STATE_REJECTED = 'rejected';
    const STATE_EXPORTED = 'exported';

    const STATE_CHOICES = [
        'admin.domestic.survey.state.new' => self::STATE_NEW,
        'admin.domestic.survey.state.invited' => self::STATE_INVITED_USER,
        'admin.domestic.survey.state.reminded' => self::STATE_REMINDED_USER,
        'admin.domestic.survey.state.in-progress' => self::STATE_IN_PROGRESS,
        'admin.domestic.survey.state.closed' => self::STATE_CLOSED,
        'admin.domestic.survey.state.rejected' => self::STATE_REJECTED,
        'admin.domestic.survey.state.exported' => self::STATE_EXPORTED,
    ];

    const REMINDER_STATE_INITIAL = "initial";
    const REMINDER_STATE_NOT_WANTED = "not-wanted";
    const REMINDER_STATE_WANTED = "wanted";
    const REMINDER_STATE_SENT = "sent";

    /**
     * @ORM\Column(type="boolean")
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
     * @ORM\Column(type="string", length=12)
     */
    private $reminderState;

    /**
     * @ORM\OneToOne(targetEntity=PasscodeUser::class, mappedBy="domesticSurvey", cascade={"persist", "remove"})
     */
    private $passcodeUser;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Email(groups={"add_survey"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"add_survey"})
     */
    private $invitationEmail;

    /**
     * @ORM\Embedded(class=LongAddress::class)
     * @AppAssert\ValidAddress(groups={"add_survey"}, validatePostcode=true, allowBlank=true)
     *
     * @var LongAddress|null
     */
    private $invitationAddress;

    /**
     * @Assert\Callback(groups={"add_survey"})
     */
    public function validInvitationDetails(ExecutionContextInterface $context) {
        if (!$this->invitationEmail && (!$this->invitationAddress || !$this->invitationAddress->isFilled())) {
            $context
                ->buildViolation('domestic.add.invitation-email-or-address')
                ->atPath('invitationEmail')
                ->addViolation();
        }
    }

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

    public function getReminderState(): ?string
    {
        return $this->reminderState;
    }

    public function setReminderState(string $reminderState): self
    {
        $this->reminderState = $reminderState;

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

    public function getInvitationEmail(): ?string
    {
        return $this->invitationEmail;
    }

    public function setInvitationEmail(?string $invitationEmail): self
    {
        $this->invitationEmail = $invitationEmail;

        return $this;
    }

    public function getInvitationAddress(): ?LongAddress
    {
        return $this->invitationAddress;
    }

    public function setInvitationAddress(?LongAddress $invitationAddress): self
    {
        $this->invitationAddress = $invitationAddress;

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
}
