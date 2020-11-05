<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait SurveyResponseTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $businessNature;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBusinessNature(): ?string
    {
        return $this->businessNature;
    }

    public function setBusinessNature(string $businessNature): self
    {
        $this->businessNature = $businessNature;

        return $this;
    }
}