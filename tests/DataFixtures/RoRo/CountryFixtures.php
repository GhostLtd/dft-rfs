<?php

namespace App\Tests\DataFixtures\RoRo;

use App\Entity\RoRo\Country;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CountryFixtures extends Fixture
{
    protected const COUNTRY_CODES = [
        'AL', // Albania
        'AT', // Austria
        'BY', // Belarus
        'BE', // Belgium
        'BA', // Bosnia and Herzegovina
        'BG', // Bulgaria
        'HR', // Croatia
        'CY', // Cyprus
        'CZ', // Czechia
        'DK', // Denmark
        'EE', // Estonia
        'FI', // Finland
        'FR', // France
        'GE', // Georgia
        'DE', // Germany
        'GR', // Greece
        'HU', // Hungary
        'IS', // Iceland
        'IE', // Ireland
        'IT', // Italy
        'XK', // Kosovo??
        'LV', // Latvia
        'LT', // Lithuania
        'LU', // Luxembourg
        'MT', // Malta
        'MD', // Moldova (the Republic of)
        'ME', // Montenegro
        'NL', // Netherlands
        'MK', // North Macedonia
        'NO', // Norway
        'PL', // Poland
        'PT', // Portugal
        'RO', // Romania
        'RU', // Russian Federation
        'RS', // Serbia
        'SK', // Slovakia
        'SI', // Slovenia
        'ES', // Spain
        'SE', // Sweden
        'CH', // Switzerland
        'TR', // Turkey
        'UA', // Ukraine
        'GB', // United Kingdom
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach(self::COUNTRY_CODES as $code) {
            $country = (new Country())
                ->setCode($code);

            $manager->persist($country);
        }

        $manager->flush();
    }
}