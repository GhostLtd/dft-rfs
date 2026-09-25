<?php

namespace App\Utility\AlphagovNotify;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\HaulageSurveyInterface;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use App\Utility\LoginLinkToken;
use App\Utility\PasscodeFormatter;
use App\Utility\PasscodeGenerator;
use App\Utility\RegistrationMarkHelper;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PersonalisationHelper
{
    public function __construct(protected PasscodeGenerator $passcodeGenerator, protected UrlGeneratorInterface $urlGenerator)
    {}

    public function getForEntity(object $entity): array
    {
        return match (true) {
            $entity instanceof DomesticSurvey => $this->getForDomesticSurvey($entity),
            $entity instanceof InternationalSurvey => $this->getForInternationalSurvey($entity),
            $entity instanceof PreEnquiry => $this->getForPreEnquiry($entity),
            default => throw new \LogicException("unexpected entity class: " . $entity::class),
        };
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

        $code1 = $survey->getPasscodeUser()->getUsername();

        return [
            'passcode1' => PasscodeFormatter::formatPasscode($code1),
            'passcode2' => PasscodeFormatter::formatPasscode($this->passcodeGenerator->getPasswordForUser($survey->getPasscodeUser())),
            'loginLink' => $this->getLoginLink($code1),
        ];
    }

    /** The short link that prefills code 1. Code 1 is always 8 digits; LoginLinkToken::encode() throws otherwise. */
    public function getLoginLink(string $code1): string
    {
        return $this->urlGenerator->generate('app_login_short', ['token' => LoginLinkToken::encode($code1)], UrlGeneratorInterface::ABSOLUTE_URL);
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
