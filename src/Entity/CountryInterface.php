<?php

namespace App\Entity;

interface CountryInterface
{
    public function getCountry(): ?string;
    public function setCountry(?string $country): self;
    public function getCountryOther(): ?string;
    public function setCountryOther(?string $countryOther): self;
}