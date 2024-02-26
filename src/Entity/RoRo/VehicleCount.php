<?php

namespace App\Entity\RoRo;

use App\Entity\IdTrait;
use App\Repository\RoRo\CountryVehicleCountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Table(name: 'roro_vehicle_count')]
#[UniqueConstraint(columns: ['survey_id', 'country_code', 'other_code'])]
#[ORM\Entity(repositoryClass: CountryVehicleCountRepository::class)]
class VehicleCount
{
    public const OTHER_CODE_OTHER = 'other';
    public const OTHER_CODE_UNKNOWN = 'unknown';
    public const OTHER_CODE_UNACCOMPANIED_TRAILERS = 'trailers';

    use IdTrait;

    #[ORM\Column(type: Types::STRING, length: 2, nullable: true)]
    private ?string $countryCode = null;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    private ?string $otherCode = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $vehicleCount = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: Survey::class, inversedBy: 'vehicleCounts')]
    private ?Survey $survey = null;

    protected ?string $label = null;

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function getOtherCode(): ?string
    {
        return $this->otherCode;
    }

    public function setOtherCode(?string $otherCode): self
    {
        $this->otherCode = $otherCode;
        return $this;
    }

    public function getVehicleCount(): ?int
    {
        return $this->vehicleCount;
    }

    public function setVehicleCount(?int $vehicleCount): self
    {
        $this->vehicleCount = $vehicleCount;
        return $this;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(?Survey $survey): self
    {
        $this->survey = $survey;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }
}
