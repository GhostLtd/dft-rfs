<?php


namespace App\Utility\AlphagovNotify;


use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
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

    private static function getForAllSurveys(SurveyInterface $survey)
    {
        return [
            'surveyPeriodStart' => $survey->getSurveyPeriodStart()->format('l, jS F Y'),
            'surveyPeriodEnd' => $survey->getSurveyPeriodEnd()->format('l, jS F Y'),
            'passcode1' => $survey->getPasscodeUser() ? $survey->getPasscodeUser()->getUsername() : 'unknown',
            'passcode2' => $survey->getPasscodeUser() ? $survey->getPasscodeUser()->getPlainPassword() : 'unknown',
        ];
    }
}