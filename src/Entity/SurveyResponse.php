<?php


namespace App\Entity;


abstract class SurveyResponse
{
    // https://www.ons.gov.uk/businessindustryandtrade/business/activitysizeandlocation/adhocs/007855enterprisesintheunitedkingdombyemployeesizeband
    // https://www.thecompanywarehouse.co.uk/blog/what-is-an-sme
    const EMPLOYEES_1_TO_9 = '1-9';
    const EMPLOYEES_10_TO_49 = '10-49';
    const EMPLOYEES_50_TO_249 = '50-249';
    const EMPLOYEES_250_TO_499 = '250-499';
    const EMPLOYEES_500_TO_10000 = '500-10000';
    const EMPLOYEES_10001_TO_30000 = '10001-30000';
    const EMPLOYEES_MORE_THAN_30000 = '>30000';

    const EMPLOYEES_TRANSLATION_PREFIX = 'common.number-of-employees.';
    const EMPLOYEES_CHOICES = [
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_1_TO_9 => self::EMPLOYEES_1_TO_9,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_10_TO_49 => self::EMPLOYEES_10_TO_49,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_50_TO_249 => self::EMPLOYEES_50_TO_249,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_250_TO_499 => self::EMPLOYEES_250_TO_499,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_500_TO_10000 => self::EMPLOYEES_500_TO_10000,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_10001_TO_30000 => self::EMPLOYEES_10001_TO_30000,
        self::EMPLOYEES_TRANSLATION_PREFIX . self::EMPLOYEES_MORE_THAN_30000 => self::EMPLOYEES_MORE_THAN_30000,
    ];
}