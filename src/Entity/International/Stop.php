<?php

namespace App\Entity\International;

use App\Repository\International\StopRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StopRepository::class)
 * @ORM\Table(name="international_stop")
 */
class Stop
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Trip::class, inversedBy="stops")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trip;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $country;

    /**
     * @ORM\Column(type="smallint")
     */
    private $number;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): self
    {
        $this->trip = $trip;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }
}
