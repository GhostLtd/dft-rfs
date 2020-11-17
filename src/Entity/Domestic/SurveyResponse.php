<?php

namespace App\Entity\Domestic;

use App\Entity\Address;
use App\Entity\SurveyResponseTrait;
use App\Repository\Domestic\SurveyResponseRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=SurveyResponseRepository::class)
 */
class SurveyResponse
{
    const REASON_SCRAPPED_OR_STOLEN = 'scrapped-or-stolen';
    const REASON_SOLD = 'sold';
    const REASON_ON_HIRE = 'on-hire';
    const REASON_NOT_TAXED = 'not-taxed';
    const REASON_NO_WORK = 'no-work';
    const REASON_REPAIR = 'repair';
    const REASON_SITE_WORK_ONLY = 'site-work-only';
    const REASON_HOLIDAY = 'holiday';
    const REASON_MAINTENANCE = 'maintenance';
    const REASON_NO_DRIVER = 'no-driver';
    const REASON_OTHER = 'other';

    const REASON_TRANSLATION_PREFIX = 'survey.domestic.non-complete.';

    const UNABLE_TO_COMPLETE_REASON_CHOICES = [
        self::REASON_TRANSLATION_PREFIX . self::REASON_SCRAPPED_OR_STOLEN => self::REASON_SCRAPPED_OR_STOLEN,
        self::REASON_TRANSLATION_PREFIX . self::REASON_SOLD => self::REASON_SOLD,
        self::REASON_TRANSLATION_PREFIX . self::REASON_ON_HIRE => self::REASON_ON_HIRE,
    ];

    const EMPTY_SURVEY_REASON_CHOICES = [
        self::REASON_TRANSLATION_PREFIX . self::REASON_NOT_TAXED => self::REASON_NOT_TAXED,
        self::REASON_TRANSLATION_PREFIX . self::REASON_NO_WORK => self::REASON_NO_WORK,
        self::REASON_TRANSLATION_PREFIX . self::REASON_REPAIR => self::REASON_REPAIR,
        self::REASON_TRANSLATION_PREFIX . self::REASON_SITE_WORK_ONLY => self::REASON_SITE_WORK_ONLY,
        self::REASON_TRANSLATION_PREFIX . self::REASON_HOLIDAY => self::REASON_HOLIDAY,
        self::REASON_TRANSLATION_PREFIX . self::REASON_MAINTENANCE => self::REASON_MAINTENANCE,
        self::REASON_TRANSLATION_PREFIX . self::REASON_NO_DRIVER => self::REASON_NO_DRIVER,
        self::REASON_TRANSLATION_PREFIX . self::REASON_OTHER => self::REASON_OTHER,
    ];

    use SurveyResponseTrait;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $numberOfEmployees;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"hiree_details"})
     */
    private $hireeName;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"hiree_details"})
     * @Assert\Email(groups={"hiree_details"})
     */
    private $hireeEmail;

    /**
     * @ORM\Embedded(class=Address::class)
     * @Assert\Valid(groups={"hiree_details"})
     */
    private $hireeAddress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"sold_details"})
     */
    private $newOwnerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"sold_details"})
     * @Assert\Email(groups={"sold_details"})
     */
    private $newOwnerEmail;

    /**
     * @ORM\Embedded(class=Address::class)
     * @Assert\Valid(groups={"sold_details"})
     */
    private $newOwnerAddress;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $unableToCompleteDate;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    private $unableToCompleteReason;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, inversedBy="response", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $survey;

    /**
     * @ORM\OneToOne(targetEntity=Vehicle::class, cascade={"persist"})
     */
    private $vehicle;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $actualVehicleLocation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ableToComplete;

    /**
     * @var Day[]
     * @ORM\OneToMany(targetEntity=Day::class, mappedBy="response", orphanRemoval=true)
     */
    private $days;

    public function __construct()
    {
        $this->days = new ArrayCollection();
    }

    public function getNumberOfEmployees(): ?int
    {
        return $this->numberOfEmployees;
    }

    public function setNumberOfEmployees(int $numberOfEmployees): self
    {
        $this->numberOfEmployees = $numberOfEmployees;

        return $this;
    }

    public function getHireeName(): ?string
    {
        return $this->hireeName;
    }

    public function setHireeName(string $hireeName): self
    {
        $this->hireeName = $hireeName;

        return $this;
    }

    public function getHireeEmail(): ?string
    {
        return $this->hireeEmail;
    }

    public function setHireeEmail(string $hireeEmail): self
    {
        $this->hireeEmail = $hireeEmail;

        return $this;
    }

    public function getHireeAddress(): ?Address
    {
        return $this->hireeAddress;
    }

    public function setHireeAddress(Address $hireeAddress): self
    {
        $this->hireeAddress = $hireeAddress;

        return $this;
    }

    public function getNewOwnerAddress(): ?Address
    {
        return $this->newOwnerAddress;
    }

    public function setNewOwnerAddress(Address $newOwnerAddress): self
    {
        $this->newOwnerAddress = $newOwnerAddress;

        return $this;
    }

    public function getUnableToCompleteDate(): ?DateTimeInterface
    {
        return $this->unableToCompleteDate;
    }

    public function setUnableToCompleteDate(?DateTimeInterface $unableToCompleteDate): self
    {
        $this->unableToCompleteDate = $unableToCompleteDate;

        return $this;
    }

    public function getUnableToCompleteReason(): ?string
    {
        return $this->unableToCompleteReason;
    }

    public function setUnableToCompleteReason(?string $unableToCompleteReason): self
    {
        $this->unableToCompleteReason = $unableToCompleteReason;

        return $this;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(Survey $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getActualVehicleLocation(): ?string
    {
        return $this->actualVehicleLocation;
    }

    public function setActualVehicleLocation(?string $actualVehicleLocation): self
    {
        $this->actualVehicleLocation = $actualVehicleLocation;

        return $this;
    }

    public function getAbleToComplete(): ?bool
    {
        return $this->ableToComplete;
    }

    public function setAbleToComplete(?bool $ableToComplete): self
    {
        $this->ableToComplete = $ableToComplete;
        if ($ableToComplete) $this->setUnableToCompleteReason(null);

        return $this;
    }

    public function getNewOwnerName(): ?string
    {
        return $this->newOwnerName;
    }

    public function setNewOwnerName(?string $newOwnerName): self
    {
        $this->newOwnerName = $newOwnerName;

        return $this;
    }

    public function getNewOwnerEmail(): ?string
    {
        return $this->newOwnerEmail;
    }

    public function setNewOwnerEmail(?string $newOwnerEmail): self
    {
        $this->newOwnerEmail = $newOwnerEmail;

        return $this;
    }

    /**
     * @return Collection|Day[]
     */
    public function getDays(): Collection
    {
        return $this->days;
    }

    public function addStopDay(Day $stopDay): self
    {
        if (!$this->days->contains($stopDay)) {
            $this->days[] = $stopDay;
            $stopDay->setResponse($this);
        }

        return $this;
    }

    public function removeStopDay(Day $stopDay): self
    {
        if ($this->days->removeElement($stopDay)) {
            // set the owning side to null (unless already changed)
            if ($stopDay->getResponse() === $this) {
                $stopDay->setResponse(null);
            }
        }

        return $this;
    }

    public function getStopDayByNumber($dayNumber)
    {
        foreach ($this->days as $day) {
            if ($day->getNumber() === $dayNumber) {
                return $day;
            }
        }
        return null;
    }
}
