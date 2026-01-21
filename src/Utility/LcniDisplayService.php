<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use App\Repository\NotificationInterceptionRepository;
use Symfony\Component\Lock\LockFactory;

class LcniDisplayService
{
    public const string DVLA_IMPORT_LOCK_NAME = 'lcni-dvla-import';

    public function __construct(
        private readonly NotificationInterceptionRepository $notificationInterceptionRepository,
        private readonly LockFactory $lockFactory,
    ) {
    }

    /**
     * Get LCNI email info for display on admin screens.
     * Returns null if no LCNI match or if matched record has no emails.
     * Returns ['locked' => true] if DVLA import is in progress.
     *
     * @return array{emails: string[]}|array{locked: true}|null
     */
    public function getDisplayInfoForSurvey(SurveyInterface $survey): ?array
    {
        if ($this->isDvlaImportInProgress()) {
            return ['locked' => true];
        }

        $businessName = $this->getBusinessName($survey);
        if ($businessName === null) {
            return null;
        }

        $surveyType = $this->getSurveyType($survey);

        return $this->getDisplayInfo($businessName, $surveyType);
    }

    /**
     * Get LCNI email info for a company name (used by AJAX lookup).
     * Returns ['locked' => true] if DVLA import is in progress.
     *
     * @return array{emails: string[]}|array{locked: true}|null
     */
    public function getDisplayInfoForCompanyName(string $companyName, string $surveyType): ?array
    {
        if ($this->isDvlaImportInProgress()) {
            return ['locked' => true];
        }

        return $this->getDisplayInfo($companyName, $surveyType);
    }

    /**
     * @return array{emails: string[]}|null
     */
    private function getDisplayInfo(string $businessName, string $surveyType): ?array
    {
        $interception = $this->notificationInterceptionRepository->findOneByBusinessName($businessName);
        if (!$interception) {
            return null;
        }

        $lcniEmails = $interception->getAggregatedEmailsArray($surveyType);
        if (empty($lcniEmails)) {
            return null;
        }

        return [
            'emails' => $lcniEmails,
        ];
    }

    private function isDvlaImportInProgress(): bool
    {
        $lock = $this->lockFactory->createLock(self::DVLA_IMPORT_LOCK_NAME, 60 * 2, false);

        if (!$lock->acquire()) {
            return true;
        }

        $lock->release();
        return false;
    }

    private function getBusinessName(SurveyInterface $survey): ?string
    {
        return match (true) {
            $survey instanceof DomesticSurvey => $survey->getInvitationAddress()?->getLine1(),
            $survey instanceof InternationalSurvey => $survey->getCompany()?->getBusinessName(),
            default => null,
        };
    }

    private function getSurveyType(SurveyInterface $survey): string
    {
        return match (true) {
            $survey instanceof DomesticSurvey => 'csrgt',
            $survey instanceof InternationalSurvey => 'irhs',
            default => throw new \InvalidArgumentException('Unknown survey type'),
        };
    }
}
