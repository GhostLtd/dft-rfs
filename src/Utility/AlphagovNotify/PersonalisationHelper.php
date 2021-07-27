<?php


namespace App\Utility\AlphagovNotify;


use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\HaulageSurveyInterface;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Utility\RegistrationMarkHelper;

class PersonalisationHelper
{
    public static function getForEntity($entity)
    {
        switch (get_class($entity)) {
            case DomesticSurvey::class :
                return self::getForDomesticSurvey($entity);
            case InternationalSurvey::class :
                return self::getForInternationalSurvey($entity);
            case PreEnquiry::class:
                return self::getForPreEnquiry($entity);
        }
        throw new \LogicException("unexpected entity class: " . get_class($entity));
    }

    public static function getForDomesticSurvey(DomesticSurvey $survey)
    {
        return array_merge(self::getForAllSurveys($survey), [
            'registrationMark' => (new RegistrationMarkHelper($survey->getRegistrationMark()))->getFormattedRegistrationMark(),
        ]);
    }

    public static function getForInternationalSurvey(InternationalSurvey $survey)
    {
        return array_merge(self::getForAllSurveys($survey), [
            'surveyReference' => $survey->getReferenceNumber(),
        ]);
    }

    public static function getForPreEnquiry(PreEnquiry $preEnquiry)
    {
        return [
            'passcode1' => $preEnquiry->getPasscodeUser() ? $preEnquiry->getPasscodeUser()->getUsername() : 'unknown',
            'passcode2' => $preEnquiry->getPasscodeUser() ? $preEnquiry->getPasscodeUser()->getPlainPassword() : 'unknown',
        ];
    }

    private static function getForAllSurveys(HaulageSurveyInterface $survey)
    {
        $periodStart = $survey->getSurveyPeriodStart()->format('l, jS F Y');
        $periodEnd = $survey->getSurveyPeriodEnd()->format('l, jS F Y');
        return [
            'surveyPeriodStart' => $periodStart,
            'surveyPeriodEnd' => $periodEnd,
            'surveyPeriod' => ($periodStart === $periodEnd) ? $periodStart : "{$periodStart} to {$periodEnd}",
            'passcode1' => $survey->getPasscodeUser() ? $survey->getPasscodeUser()->getUsername() : 'unknown',
            'passcode2' => $survey->getPasscodeUser() ? $survey->getPasscodeUser()->getPlainPassword() : 'unknown',
        ];
    }
}