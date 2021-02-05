<?php

namespace App\Utility;

use App\Entity\CountryInterface;
use App\Form\CountryType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;

class CountryHelper
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getCountryLabel(CountryInterface $entity): ?string
    {
        $country = $entity->getCountry();
        return ($country === CountryType::OTHER || $country === null) ?
            $entity->getCountryOther() :
            Countries::getName(strtoupper($country), $this->requestStack->getCurrentRequest()->getLocale());
    }
}