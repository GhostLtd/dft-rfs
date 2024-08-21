<?php

namespace App\Entity\International;

use App\Entity\VehicleInterface;
use App\Entity\VehicleTrait;
use App\Form\Validator as AppAssert;
use App\Repository\International\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[AppAssert\ValidRegistration(groups: ["vehicle_registration", "admin_vehicle"])]
#[ORM\Table(name: 'international_vehicle')]
#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle implements VehicleInterface
{
    use VehicleTrait;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: SurveyResponse::class, inversedBy: 'vehicles')]
    private ?SurveyResponse $surveyResponse = null;

    /**
     * @var Collection<int, Trip>
     */
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Trip::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['outboundDate' => 'ASC', 'returnDate' => 'ASC'])]
    private Collection $trips;

    public function __construct()
    {
        $this->trips = new ArrayCollection();
    }

    public function getSurveyResponse(): ?SurveyResponse
    {
        return $this->surveyResponse;
    }

    public function setSurveyResponse(?SurveyResponse $surveyResponse): self
    {
        $this->surveyResponse = $surveyResponse;
        return $this;
    }

    /**
     * @return Collection<Trip>
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trip $trip): self
    {
        if (!$this->trips->contains($trip)) {
            $this->trips[] = $trip;
            $trip->setVehicle($this);
        }

        return $this;
    }

    public function removeTrip(Trip $trip): self
    {
        if ($this->trips->removeElement($trip)) {
            // set the owning side to null (unless already changed)
            if ($trip->getVehicle() === $this) {
                $trip->setVehicle(null);
            }
        }

        return $this;
    }

    public function mergeVehicleChanges(Vehicle $vehicle): void
    {
        $this->setRegistrationMark($vehicle->getRegistrationMark());
        $this->setOperationType($vehicle->getOperationType());
        $this->setAxleConfiguration($vehicle->getAxleConfiguration());
        $this->setTrailerConfiguration($vehicle->getTrailerConfiguration()); // Order is important
        $this->setBodyType($vehicle->getBodyType());
        $this->setCarryingCapacity($vehicle->getCarryingCapacity());
        $this->setGrossWeight($vehicle->getGrossWeight());
    }
}
