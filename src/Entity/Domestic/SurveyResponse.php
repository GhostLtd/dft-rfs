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
 * @ORM\Table("domestic_survey_response")
 */
class SurveyResponse
{
    const IN_POSSESSION_YES = 'yes';
    const IN_POSSESSION_SCRAPPED_OR_STOLEN = 'scrapped-or-stolen';
    const IN_POSSESSION_SOLD = 'sold';
    const IN_POSSESSION_ON_HIRE = 'on-hire';

    const REASON_NOT_TAXED = 'not-taxed';
    const REASON_NO_WORK = 'no-work';
    const REASON_REPAIR = 'repair';
    const REASON_SITE_WORK_ONLY = 'site-work-only';
    const REASON_HOLIDAY = 'holiday';
    const REASON_MAINTENANCE = 'maintenance';
    const REASON_NO_DRIVER = 'no-driver';
    const REASON_OTHER = 'other';

    const IN_POSSESSION_TRANSLATION_PREFIX = 'domestic.survey-response.in-possession-of-vehicle.option.';

    const IN_POSSESSION_CHOICES = [
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_YES => self::IN_POSSESSION_YES,
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_SCRAPPED_OR_STOLEN => self::IN_POSSESSION_SCRAPPED_OR_STOLEN,
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_SOLD => self::IN_POSSESSION_SOLD,
        self::IN_POSSESSION_TRANSLATION_PREFIX . self::IN_POSSESSION_ON_HIRE => self::IN_POSSESSION_ON_HIRE,
    ];

    const EMPTY_SURVEY_REASON_TRANSLATION_PREFIX = 'domestic.survey-response.unable-to-complete.reason.';
    const EMPTY_SURVEY_REASON_CHOICES = [
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NOT_TAXED => self::REASON_NOT_TAXED,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NO_WORK => self::REASON_NO_WORK,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_REPAIR => self::REASON_REPAIR,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_SITE_WORK_ONLY => self::REASON_SITE_WORK_ONLY,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_HOLIDAY => self::REASON_HOLIDAY,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_MAINTENANCE => self::REASON_MAINTENANCE,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_NO_DRIVER => self::REASON_NO_DRIVER,
        self::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX . self::REASON_OTHER => self::REASON_OTHER,
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
    private $isInPossessionOfVehicle;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, inversedBy="response")
     * @ORM\JoinColumn(nullable=false)
     */
    private $survey;

    /**
     * @ORM\OneToOne(targetEntity=Vehicle::class, cascade={"persist"})
     * @Assert\Valid(groups={
     *     "vehicle_axle_configuration",
     *     "vehicle_body_type",
     *     "vehicle_fuel_quantity",
     *     "vehicle_trailer_configuration",
     *     "vehicle_weight",
     * })
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

    public function getIsInPossessionOfVehicle(): ?string
    {
        return $this->isInPossessionOfVehicle;
    }

    public function setIsInPossessionOfVehicle(?string $isInPossessionOfVehicle): self
    {
        $this->isInPossessionOfVehicle = $isInPossessionOfVehicle;

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
        if ($ableToComplete) $this->setIsInPossessionOfVehicle(null);

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

    public function addDay(Day $day): self
    {
        if (!$this->days->contains($day)) {
            $this->days[] = $day;
            $day->setResponse($this);
        }

        return $this;
    }

    public function removeDay(Day $day): self
    {
        if ($this->days->removeElement($day)) {
            // set the owning side to null (unless already changed)
            if ($day->getResponse() === $this) {
                $day->setResponse(null);
            }
        }

        return $this;
    }

    /**
     * @param $dayNumber
     * @return Day | null
     */
    public function getDayByNumber($dayNumber): ?Day
    {
        foreach ($this->days as $day) {
            if ($day->getNumber() === $dayNumber) {
                return $day;
            }
        }
        return null;
    }
}
