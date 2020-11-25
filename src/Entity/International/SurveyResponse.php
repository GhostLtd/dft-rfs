<?php

namespace App\Entity\International;

use App\Entity\SurveyResponseTrait;
use App\Repository\International\SurveyResponseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=SurveyResponseRepository::class)
 * @ORM\Table(name="international_survey_response")
 */
class SurveyResponse
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

    const NOT_USED_NO_INTERNATIONAL_WORK = 'no-international_work';
    const NOT_USED_ON_HOLIDAY = 'on-holiday';
    const NOT_USED_REPAIR = 'repair';
    const NOT_USED_NO_VEHICLES_AVAILABLE = 'no-vehicles';
    const NOT_USED_OTHER = 'other';

    const NOT_USED_PREFIX = 'international.survey-response.not-used.';
    const NOT_USED_CHOICES_PREFIX = self::NOT_USED_PREFIX . 'choices.';
    const NOT_USED_CHOICES = [
        self::NOT_USED_CHOICES_PREFIX . self::NOT_USED_NO_INTERNATIONAL_WORK => self::NOT_USED_NO_INTERNATIONAL_WORK,
        self::NOT_USED_CHOICES_PREFIX . self::NOT_USED_ON_HOLIDAY => self::NOT_USED_ON_HOLIDAY,
        self::NOT_USED_CHOICES_PREFIX . self::NOT_USED_REPAIR => self::NOT_USED_REPAIR,
        self::NOT_USED_CHOICES_PREFIX . self::NOT_USED_NO_VEHICLES_AVAILABLE => self::NOT_USED_NO_VEHICLES_AVAILABLE,
        self::NOT_USED_CHOICES_PREFIX . self::NOT_USED_OTHER => self::NOT_USED_OTHER,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotNull(groups={"number_of_trips"}, message="international.survey-response.annual-international-journey-count.not-null")
     * @Assert\Range(groups={"number_of_trips"}, min=0, minMessage="international.survey-response.annual-international-journey-count.not-negative")
     */
    private $annualInternationalJourneyCount;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    private $activityStatus;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    private $notUsed;

    /**
     * @ORM\OneToOne(targetEntity=Survey::class, inversedBy="response", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $survey;

    /**
     * @ORM\OneToMany(targetEntity=Vehicle::class, mappedBy="surveyResponse", orphanRemoval=true)
     */
    private $vehicles;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $fewerThanTenEmployees;

    /**
     * @Assert\Callback(groups={"business_details"})
     */
    public function validateFewerThanTenEmployees(ExecutionContextInterface $context)
    {
        // fewerThanTenEmployees is allowed to be null ONLY IF the company is no longer active...
        if (!$this->isNoLongerActive() && $this->getFewerThanTenEmployees() === null) {
            $context
                ->buildViolation('international.survey-response.fewer-than-ten-employees.not-null')
                ->atPath('fewerThanTenEmployees')
                ->addViolation();
        }
    }

    use SurveyResponseTrait;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
    }

    public function getId(): ?int
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

        return $this;
    }

    public function getActivityStatus(): ?string
    {
        return $this->activityStatus;
    }

    public function setActivityStatus(?string $activityStatus): self
    {
        $this->activityStatus = $activityStatus;

        return $this;
    }

    public function getNotUsedStatus(): ?string
    {
        return $this->notUsed;
    }

    public function setNotUsedStatus(?string $notUsed): self
    {
        $this->notUsed = $notUsed;

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

    public function getFewerThanTenEmployees(): ?bool
    {
        return $this->fewerThanTenEmployees;
    }

    public function setFewerThanTenEmployees(?bool $fewerThanTenEmployees): self
    {
        $this->fewerThanTenEmployees = $fewerThanTenEmployees;

        return $this;
    }

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
}