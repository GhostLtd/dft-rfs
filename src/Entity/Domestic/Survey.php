<?php

namespace App\Entity\Domestic;

use App\Entity\Address;
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
class Survey
{
    use SurveyTrait;

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
     */
    private $invitationEmail;

    /**
     * @ORM\Embedded(class=Address::class)
     * @AppAssert\ValidAddress(groups={"add_survey"}, validatePostcode=true, allowBlank=true)
     *
     * @var Address|null
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

    public function getInvitationAddress(): ?Address
    {
        return $this->invitationAddress;
    }

    public function setInvitationAddress(?Address $invitationAddress): self
    {
        $this->invitationAddress = $invitationAddress;

        return $this;
    }

}
