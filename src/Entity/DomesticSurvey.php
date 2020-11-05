<?php

namespace App\Entity;

use App\Repository\DomesticSurveyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DomesticSurveyRepository::class)
 */
class DomesticSurvey
{
    use SurveyTrait;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isNorthernIreland;

    public function getIsNorthernIreland(): ?bool
    {
        return $this->isNorthernIreland;
    }

    public function setIsNorthernIreland(bool $isNorthernIreland): self
    {
        $this->isNorthernIreland = $isNorthernIreland;

        return $this;
    }
}
