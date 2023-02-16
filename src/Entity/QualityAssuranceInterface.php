<?php

namespace App\Entity;

interface QualityAssuranceInterface
{
    public function getQualityAssured(): ?bool;
    public function setQualityAssured(?bool $qualityAssured): self;
}