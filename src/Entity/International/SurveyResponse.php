<?php

namespace App\Entity\International;

use App\Entity\BlameLoggable;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use App\Entity\SurveyResponseTrait;
use App\Repository\International\SurveyResponseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=SurveyResponseRepository::class)
 * @ORM\Table(name="international_survey_response")
 */
class SurveyResponse extends AbstractSurveyResponse implements BlameLoggable
{
    const ACTIVITY_STATUS_CEASED_TRADING = 'ceased-trading';
    const ACTIVITY_STATUS_ONLY_DOMESTIC_WORK = 'only-domestic-work';
    const ACTIVITY_STATUS_STILL_ACTIVE = 'still-active';

    const ACTIVITY_STATUS_PREFIX = 'international.survey-response.activity-status.';
    const ACTIVITY_STATUS_CHOICES_PREFIX = self::ACTIVITY_STATUS_PREFIX .'choices.';
    const ACTIVITY_STATUS_CHOICES = [
         self::ACTIVITY_STATUS_CHOICES_PREFIX . self::ACTIVITY_STATUS_CEASED_TRADING => self::ACTIVITY_STATUS_CEASED_TRADING,
         self::ACTIVITY_STATUS_CHOICES_PREFIX . self::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK => self::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK,
         self::ACTIVITY_STATUS_CHOICES_PREFIX . self::ACTIVITY_STATUS_STILL_ACTIVE => self::ACTIVITY_STATUS_STILL_ACTIVE,
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
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotNull(groups={"number_of_trips", "admin_business_details"}, message="international.survey-response.annual-international-journey-count.not-null")
     * @Assert\PositiveOrZero(message="common.number.positive-or-zero", groups={"number_of_trips", "admin_business_details"})
     * @Assert\Range(groups={"number_of_trips", "admin_business_details"}, max=2000000000, maxMessage="common.number.max")
     */
    private $annualInternationalJourneyCount;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Assert\NotNull(groups={"activity_status", "admin_business_details"}, message="common.choice.invalid")
     */
    private $activityStatus;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Assert\NotNull(groups={"reason_for_empty_survey"}, message="common.choice.invalid")
     */
    private $reasonForEmptySurvey;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, inversedBy="response", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $survey;

    /**
     * @ORM\OneToMany(targetEntity=Vehicle::class, mappedBy="surveyResponse", orphanRemoval=true)
     * @ORM\OrderBy({"registrationMark": "ASC"})
     */
    private $vehicles;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $initialDetailsSignedOff;

    use SurveyResponseTrait;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAnnualInternationalJourneyCount(): ?int
    {
        return $this->annualInternationalJourneyCount;
    }

    public function setAnnualInternationalJourneyCount(?int $annualInternationalJourneyCount): self
    {
        $this->annualInternationalJourneyCount = $annualInternationalJourneyCount;

        if ($annualInternationalJourneyCount !== null) {
            if ($annualInternationalJourneyCount > 0) {
                $this->setActivityStatus(self::ACTIVITY_STATUS_STILL_ACTIVE);
            } else if ($annualInternationalJourneyCount === 0) {
                // If the journey count has just been set to 0, and we were previously still performing international
                // journeys, then the activity question needs to be a asked again
                if (!$this->isNoLongerActive()) {
                    $this->setActivityStatus(null);
                }
            }
        }

        return $this;
    }

    public function getActivityStatus(): ?string
    {
        return $this->activityStatus;
    }

    public function setActivityStatus(?string $activityStatus): self
    {
        $this->activityStatus = $activityStatus;

        if ($this->isNoLongerActive()) {
            $this->setBusinessNature(null);
            $this->setNumberOfEmployees(null);
            $this->setInitialDetailsSignedOff(null);
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

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(Survey $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    /**
     * @return Collection|Vehicle[]
     */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): self
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles[] = $vehicle;
            $vehicle->setSurveyResponse($this);
        }

        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): self
    {
        if ($this->vehicles->removeElement($vehicle)) {
            // set the owning side to null (unless already changed)
            if ($vehicle->getSurveyResponse() === $this) {
                $vehicle->setSurveyResponse(null);
            }
        }

        return $this;
    }

    public function isInitialDetailsSignedOff(): ?bool
    {
        return $this->initialDetailsSignedOff;
    }

    public function setInitialDetailsSignedOff(?bool $initialDetailsSignedOff): self
    {
        $this->initialDetailsSignedOff = $initialDetailsSignedOff;

        return $this;
    }

    // -----

    public function hasPositiveTrips(): bool
    {
        $journeyCount = $this->getAnnualInternationalJourneyCount();
        return $journeyCount !== null && $journeyCount > 0;
    }

    public function isNoLongerActive(): bool
    {
        $status = $this->getActivityStatus();
        return ($status === self::ACTIVITY_STATUS_CEASED_TRADING || $status === self::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK);
    }

    public function mergeInitialDetails(SurveyResponse $response)
    {
        $this->setContactName($response->getContactName());
        $this->setContactEmail($response->getContactEmail());
        $this->setContactTelephone($response->getContactTelephone());

        // N.B. The order of these calls is important as some setters cause other setters to be triggered
        $this->setBusinessNature($response->getBusinessNature());
        $this->setNumberOfEmployees($response->getNumberOfEmployees());
        $this->setActivityStatus($response->getActivityStatus());
        $this->setAnnualInternationalJourneyCount($response->getAnnualInternationalJourneyCount());
    }

    public function mergeClosingDetails(SurveyResponse $response)
    {
        $this->setReasonForEmptySurvey($response->getReasonForEmptySurvey());
    }

    public function canSubmit(): bool
    {
        return $this->isNoLongerActive(); // TODO: Add further check cases
    }

    public function isFilledOut(): bool
    {
        // TODO: More extensive checks
        return !$this->vehicles->isEmpty();
    }

    public function getBlameLogLabel()
    {
        return $this->getContactName();
    }

    public function getAssociatedEntityClass()
    {
        return Survey::class;
    }

    public function getAssociatedEntityId()
    {
        return $this->getSurvey()->getId();
    }
}