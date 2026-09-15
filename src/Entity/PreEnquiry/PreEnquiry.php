<?php

namespace App\Entity\PreEnquiry;

use App\Entity\NoteInterface;
use App\Entity\SurveyManualReminderInterface;
use App\Entity\SurveyNotificationInterface;
use App\Entity\SurveyNotificationsTrait;
use App\Entity\SurveyInterface;
use App\Entity\SurveyStateInterface;
use App\Entity\SurveyTrait;
use App\Entity\PasscodeUser;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Feedback; // PHPstorm indicates this isn't needed, but it is

#[ORM\Table(name: 'pre_enquiry')]
#[ORM\Entity(repositoryClass: PreEnquiryRepository::class)]
class PreEnquiry implements SurveyInterface, SurveyManualReminderInterface, SurveyStateInterface
{
    use SurveyTrait;
    use SurveyNotificationsTrait;

    public const array STATE_FILTER_CHOICES = [
        self::STATE_NEW,
        self::STATE_INVITATION_PENDING,
        self::STATE_INVITATION_SENT,
        self::STATE_INVITATION_FAILED,
        self::STATE_IN_PROGRESS,
        self::STATE_CLOSED,
        self::STATE_REJECTED,
        self::STATE_EXPORTING,
        self::STATE_EXPORTED,
    ];

    #[ORM\OneToOne(targetEntity: PreEnquiryResponse::class, mappedBy: 'preEnquiry', cascade: ['persist'])]
    private ?PreEnquiryResponse $response = null;

    #[Assert\NotBlank(message: 'common.string.not-blank', groups: ['add_survey', 'import_survey'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['add_survey', 'import_survey'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $referenceNumber = null;

    #[Assert\NotBlank(message: 'common.string.not-blank', groups: ['add_survey', 'import_survey'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['add_survey', 'import_survey'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $companyName = null;

    #[ORM\OneToOne(targetEntity: PasscodeUser::class, mappedBy: 'preEnquiry', cascade: ['persist', 'remove'])]
    private ?PasscodeUser $passcodeUser = null;

    /**
     * @var Collection<PreEnquiryNote>
     */
    #[ORM\OneToMany(targetEntity: PreEnquiryNote::class, mappedBy: 'preEnquiry')]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $notes;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dispatchDate = null;

    /**
     * @var Collection<int, PreEnquiryNotification>
     */
    #[ORM\OneToMany(targetEntity: PreEnquiryNotification::class, mappedBy: 'survey', cascade: ['persist'])]
    #[ORM\OrderBy(['sentAt' => 'ASC'])]
    private Collection $notifications;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

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

    #[\Override]
    public function getNotes(): ?Collection
    {
        return $this->notes;
    }

    /** @param $notes Collection<PreEnquiryNote> */
    #[\Override]
    public function setNotes(Collection $notes): PreEnquiry
    {
        $this->notes = $notes;
        return $this;
    }

    #[\Override]
    public function addNote(NoteInterface $note): self
    {
        if (!$note instanceof PreEnquiryNote) {
            throw new \RuntimeException("Got a ".$note::class.", but expected a ".PreEnquiryNote::class);
        }

        if (!$this->notes->contains($note)) {
            $note->setPreEnquiry($this);
            $this->notes[] = $note;
        }

        return $this;
    }

    #[\Override]
    public function createNote(): NoteInterface
    {
        return new PreEnquiryNote();
    }

    #[\Override]
    public function createNotification(): SurveyNotificationInterface
    {
        return new PreEnquiryNotification();
    }

    #[\Override]
    public static function getNotificationClass(): string
    {
        return PreEnquiryNotification::class;
    }

    #[\Override]
    public function getChasedCount(): int
    {
        return array_reduce($this->getNotes()->toArray(), fn($c, NoteInterface $i) => $c += ($i->getWasChased() ? 1 : 0)) ?? 0;
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

    #[\Override]
    public function getResponseContactEmail(): ?string
    {
        return $this->getResponse()?->getContactEmail();
    }
}
