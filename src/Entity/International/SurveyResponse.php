<?php

namespace App\Entity\International;

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
class SurveyResponse extends AbstractSurveyResponse
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

    public function canSubmit(): bool
    {
        return $this->isNoLongerActive(); // TODO: Add further check cases
    }

    public function isFilledOut(): bool
    {
        return $this->getTotalNumberOfTrips() > 0;
    }

    public function getNumberOfVehicles(): int
    {
        return $this->vehicles->count();
    }

    public function getTotalNumberOfTrips(): int
    {
        return array_sum(array_map(fn(Vehicle $v) => $v->getTrips()->count(), $this->vehicles->toArray()));
    }
}