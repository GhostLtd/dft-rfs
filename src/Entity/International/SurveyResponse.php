<?php

namespace App\Entity\International;

use App\Entity\SurveyResponse as AbstractSurveyResponse;
use App\Entity\SurveyResponseTrait;
use App\Repository\International\SurveyResponseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'international_survey_response')]
#[ORM\Entity(repositoryClass: SurveyResponseRepository::class)]
class SurveyResponse extends AbstractSurveyResponse
{
    public const ACTIVITY_STATUS_CEASED_TRADING = 'ceased-trading';
    public const ACTIVITY_STATUS_ONLY_DOMESTIC_WORK = 'only-domestic-work';
    public const ACTIVITY_STATUS_STILL_ACTIVE = 'still-active';

    public const ACTIVITY_STATUS_PREFIX = 'international.survey-response.activity-status.';
    public const ACTIVITY_STATUS_CHOICES_PREFIX = self::ACTIVITY_STATUS_PREFIX .'choices.';
    public const ACTIVITY_STATUS_CHOICES = [
         self::ACTIVITY_STATUS_CHOICES_PREFIX . self::ACTIVITY_STATUS_CEASED_TRADING => self::ACTIVITY_STATUS_CEASED_TRADING,
         self::ACTIVITY_STATUS_CHOICES_PREFIX . self::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK => self::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK,
         self::ACTIVITY_STATUS_CHOICES_PREFIX . self::ACTIVITY_STATUS_STILL_ACTIVE => self::ACTIVITY_STATUS_STILL_ACTIVE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[Assert\NotNull(message: 'international.survey-response.annual-international-journey-count.not-null', groups: ['number_of_trips', 'admin_business_details'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive-or-zero', groups: ['number_of_trips', 'admin_business_details'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['number_of_trips', 'admin_business_details'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $annualInternationalJourneyCount = null;

    #[Assert\NotNull(message: 'common.choice.invalid', groups: ['activity_status', 'admin_business_details'])]
    #[ORM\Column(type: Types::STRING, length: 24, nullable: true)]
    private ?string $activityStatus = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\OneToOne(inversedBy: 'response', targetEntity: Survey::class, cascade: ['persist', 'remove'])]
    private ?Survey $survey = null;

    /**
     * @var Collection<Vehicle>
     */
    #[ORM\OneToMany(mappedBy: 'surveyResponse', targetEntity: Vehicle::class, orphanRemoval: true)]
    #[ORM\OrderBy(['registrationMark' => 'ASC'])]
    private Collection $vehicles;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $initialDetailsSignedOff = null;

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
     * @return Collection<Vehicle>
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

    public function hasPositiveEstimatedInternationalTrips(): bool
    {
        $journeyCount = $this->getAnnualInternationalJourneyCount();
        return $journeyCount !== null && $journeyCount > 0;
    }

    public function isNoLongerActive(): bool
    {
        $status = $this->getActivityStatus();
        return ($status === self::ACTIVITY_STATUS_CEASED_TRADING || $status === self::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK);
    }

    public function mergeInitialDetails(SurveyResponse $response): void
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
