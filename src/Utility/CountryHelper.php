<?php

namespace App\Utility;

use App\Entity\CountryInterface;
use App\Form\CountryType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;

class CountryHelper
{
    public function __construct(protected RequestStack $requestStack)
    {
    }

    public function getCountryLabel(CountryInterface $entity): ?string
    {
        $country = $entity->getCountry();
        return ($country === CountryType::OTHER || $country === null) ?
            $entity->getCountryOther() :
            Countries::getName(strtoupper($country), $this->requestStack->getCurrentRequest()->getLocale());
    }
}