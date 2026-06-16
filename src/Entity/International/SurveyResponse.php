<?php

namespace App\Entity\International;

use App\Entity\SurveyResponse as AbstractSurveyResponse;
use App\Entity\SurveyResponseTrait;
use App\Repository\International\SurveyResponseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'international_survey_response')]
#[ORM\Entity(repositoryClass: SurveyResponseRepository::class)]
class SurveyResponse extends AbstractSurveyResponse
{
    public const string ACTIVITY_STATUS_CEASED_TRADING = 'ceased-trading';
    public const string ACTIVITY_STATUS_ONLY_DOMESTIC_WORK = 'only-domestic-work';
    public const string ACTIVITY_STATUS_NO_ROAD_HAULAGE = 'no-road-haulage';
    public const string ACTIVITY_STATUS_STILL_ACTIVE = 'still-active';

    public const array ACTIVITY_STATUS_CHOICES = [
         'international.survey-response.activity-status.choices.ceased-trading' => self::ACTIVITY_STATUS_CEASED_TRADING,
         'international.survey-response.activity-status.choices.only-domestic-work' => self::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK,
         'international.survey-response.activity-status.choices.no-road-haulage' => self::ACTIVITY_STATUS_NO_ROAD_HAULAGE,
         'international.survey-response.activity-status.choices.still-active' => self::ACTIVITY_STATUS_STILL_ACTIVE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[Assert\NotNull(message: 'international.survey-response.annual-international-journey-count.not-null', groups: ['number_of_trips'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive-or-zero', groups: ['number_of_trips'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['number_of_trips'])]
    #[ORM\Column(name: 'annual_international_hgv_journey_count', type: Types::INTEGER, nullable: true)]
    private ?int $annualInternationalHGVJourneyCount = null;

    #[Assert\NotNull(message: 'international.survey-response.annual-international-journey-count.not-null', groups: ['number_of_trips'])]
    #[Assert\PositiveOrZero(message: 'common.number.positive-or-zero', groups: ['number_of_trips'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['number_of_trips'])]
    #[ORM\Column(name: 'annual_international_lgv_journey_count', type: Types::INTEGER, nullable: true)]
    private ?int $annualInternationalLGVJourneyCount = null;

    #[Assert\NotNull(message: 'common.choice.invalid', groups: ['activity_status', 'admin_business_details'])]
    #[ORM\Column(type: Types::STRING, length: 24, nullable: true)]
    private ?string $activityStatus = null;

    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['activity_status', 'admin_business_details'])]
    #[Assert\Expression(
        "(this.getActivityStatus() != constant('\\\\App\\\\Entity\\\\International\\\\SurveyResponse::ACTIVITY_STATUS_NO_ROAD_HAULAGE')) || value != null",
        message: 'international.survey-response.activity-status.other.not-null',
        groups: ['activity_status', 'admin_business_details']
    )]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $activityStatusOther = null;


    #[ORM\JoinColumn(nullable: false)]
    #[ORM\OneToOne(targetEntity: Survey::class, inversedBy: 'response', cascade: ['persist', 'remove'])]
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

    public function getAnnualInternationalHGVJourneyCount(): ?int
    {
        return $this->annualInternationalHGVJourneyCount;
    }

    public function setAnnualInternationalHGVJourneyCount(?int $annualInternationalHGVJourneyCount): self
    {
        $this->annualInternationalHGVJourneyCount = $annualInternationalHGVJourneyCount;
        return $this;
    }

    public function getAnnualInternationalLGVJourneyCount(): ?int
    {
        return $this->annualInternationalLGVJourneyCount;
    }

    public function setAnnualInternationalLGVJourneyCount(?int $annualInternationalLGVJourneyCount): self
    {
        $this->annualInternationalLGVJourneyCount = $annualInternationalLGVJourneyCount;
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
            $this->setAnnualInternationalHGVJourneyCount(null);
            $this->setAnnualInternationalLGVJourneyCount(null);
            $this->setInitialDetailsSignedOff(null);
        }

        if ($activityStatus !== self::ACTIVITY_STATUS_NO_ROAD_HAULAGE) {
            $this->setActivityStatusOther(null);
        }

        return $this;
    }

    public function getActivityStatusOther(): ?string
    {
        return $this->activityStatusOther;
    }

    public function setActivityStatusOther(?string $activityStatusOther): self
    {
        $this->activityStatusOther = $activityStatusOther;
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

    public function isNoLongerActive(): bool
    {
        return match($this->getActivityStatus()) {
            self::ACTIVITY_STATUS_CEASED_TRADING,
            self::ACTIVITY_STATUS_NO_ROAD_HAULAGE,
            self::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK => true,
            default => false,
        };
    }

    public function shouldSurveyBeSubmittedWithoutJourneys(): bool
    {
        return $this->isNoLongerActive();
    }

    public function mergeInitialDetails(SurveyResponse $response): void
    {
        $this->setContactName($response->getContactName());
        $this->setContactEmail($response->getContactEmail());
        $this->setContactTelephone($response->getContactTelephone());

        // N.B. The order of these calls is important as some setters cause other setters to be triggered
        $this->setBusinessNature($response->getBusinessNature());
        $this->setNumberOfEmployees($response->getNumberOfEmployees());
        $this->setAnnualInternationalHGVJourneyCount($response->getAnnualInternationalHGVJourneyCount());
        $this->setAnnualInternationalLGVJourneyCount($response->getAnnualInternationalLGVJourneyCount());
        $this->setActivityStatus($response->getActivityStatus());
        $this->setActivityStatusOther($response->getActivityStatusOther());
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
