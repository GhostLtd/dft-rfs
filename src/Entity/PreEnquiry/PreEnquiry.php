<?php

namespace App\Entity\PreEnquiry;

use App\Entity\International\Company;
use App\Entity\SurveyInterface;
use App\Entity\SurveyTrait;
use App\Entity\PasscodeUser;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=PreEnquiryRepository::class)
 * @ORM\Table(name="pre_enquiry")
 */
class PreEnquiry implements SurveyInterface
{
    use SurveyTrait;

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

    /**
     * @ORM\OneToOne(targetEntity=PreEnquiryResponse::class, mappedBy="preEnquiry", cascade={"persist"})
     */
    private ?PreEnquiryResponse $response = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="common.string.not-blank", groups={"add_survey", "import_survey"})
     * @Assert\Length (max=255, maxMessage="common.string.max-length", groups={"add_survey", "import_survey"})
     */
    private ?string $referenceNumber = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="common.string.not-blank", groups={"add_survey", "import_survey"})
     * @Assert\Length (max=255, maxMessage="common.string.max-length", groups={"add_survey", "import_survey"})
     */
    private ?string $companyName;

    /**
     * @ORM\OneToOne(targetEntity=PasscodeUser::class, mappedBy="preEnquiry", cascade={"persist", "remove"})
     */
    private ?PasscodeUser $passcodeUser = null;

    /**
     * @ORM\OneToMany(targetEntity=PreEnquiryNote::class, mappedBy="preEnquiry")
     * @ORM\OrderBy({"createdAt" = "ASC"})
     */
    private $notes;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?\DateTime $dispatchDate = null;


    public function getResponse(): ?PreEnquiryResponse
    {
        return $this->response;
    }

    public function setResponse(PreEnquiryResponse $response): self
    {
        $this->response = $response;

        // set the owning side of the relation if necessary
        if ($response->getPreEnquiry() !== $this) {
            $response->setPreEnquiry($this);
        }

        return $this;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(?string $referenceNumber): self
    {
        $this->referenceNumber = $referenceNumber;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        if (!isset($this->companyName)) return null;
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function addNote(PreEnquiryNote $note): self
    {
        if (!$this->notes->contains($note)) {
            $note->setPreEnquiry($this);
            $this->notes[] = $note;
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
        $newPreEnquiry = null === $passcodeUser ? null : $this;
        if ($passcodeUser->getPreEnquiry() !== $newPreEnquiry) {
            $passcodeUser->setPreEnquiry($newPreEnquiry);
        }

        return $this;
    }

    public function getDispatchDate(): ?\DateTime
    {
        return $this->dispatchDate;
    }

    public function setDispatchDate(?\DateTime $dispatchDate): self
    {
        $this->dispatchDate = $dispatchDate;

        return $this;
    }
}
