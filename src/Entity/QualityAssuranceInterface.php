<?php

namespace App\Entity;

interface QualityAssuranceInterface
{
    public function getId(): ?string;
    public function getQualityAssured(): ?bool;
    public function setQualityAssured(?bool $qualityAssured): self;
}