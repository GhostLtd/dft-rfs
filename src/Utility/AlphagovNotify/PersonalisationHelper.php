<?php


namespace App\Utility\AlphagovNotify;


use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\HaulageSurveyInterface;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use App\Utility\PasscodeGenerator;
use App\Utility\RegistrationMarkHelper;
use Exception;

class PersonalisationHelper
{
    private PasscodeGenerator $passcodeGenerator;

    public function __construct(PasscodeGenerator $passcodeGenerator)
    {
        $this->passcodeGenerator = $passcodeGenerator;
    }

    public function getForEntity($entity): array
    {
        switch (true) {
            case ($entity instanceof DomesticSurvey):
                return $this->getForDomesticSurvey($entity);
            case ($entity instanceof InternationalSurvey):
                return $this->getForInternationalSurvey($entity);
            case ($entity instanceof PreEnquiry):
                return $this->getForPreEnquiry($entity);
        }
        throw new \LogicException("unexpected entity class: " . get_class($entity));
    }

    private function getForDomesticSurvey(DomesticSurvey $survey): array
    {
        return array_merge($this->getForAllSurveys($survey), $this->getForHaulageSurveys($survey), [
            'registrationMark' => (new RegistrationMarkHelper($survey->getRegistrationMark()))->getFormattedRegistrationMark(),
        ]);
    }

    private function getForInternationalSurvey(InternationalSurvey $survey): array
    {
        return array_merge($this->getForAllSurveys($survey), $this->getForHaulageSurveys($survey), [
            'surveyReference' => $survey->getReferenceNumber(),
        ]);
    }

    private function getForPreEnquiry(PreEnquiry $preEnquiry): array
    {
        return array_merge($this->getForAllSurveys($preEnquiry), [
            'surveyReference' => $preEnquiry->getReferenceNumber(),
        ]);
    }

    private function getForAllSurveys(SurveyInterface $survey): array
    {
        if (!$survey->getPasscodeUser()) {
            throw new Exception('PasscodeUser entity expected');
        }
        return [
            'passcode1' => $survey->getPasscodeUser()->getUsername(),
            'passcode2' => $this->passcodeGenerator->getPasswordForUser($survey->getPasscodeUser()),
        ];
    }

    private function getForHaulageSurveys(HaulageSurveyInterface $survey): array
    {
        $periodStart = $survey->getSurveyPeriodStart()->format('l jS F Y');
        $periodEnd = $survey->getSurveyPeriodEnd()->format('l jS F Y');
        return [
            'surveyPeriodStart' => $periodStart,
            'surveyPeriodEnd' => $periodEnd,
            'surveyPeriod' => ($periodStart === $periodEnd) ? $periodStart : "{$periodStart} to {$periodEnd}",
        ];
    }
}