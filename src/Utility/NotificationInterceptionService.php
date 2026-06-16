<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\NotificationInterception;
use App\Entity\SurveyInterface;
use App\Entity\SurveyManualReminderInterface;
use App\Entity\SurveyReminderInterface;
use App\Repository\NotificationInterceptionRepository;

class NotificationInterceptionService
{
    public function __construct(
        protected NotificationInterceptionRepository $notificationInterceptionRepository,
        protected EmailBlacklistService $emailBlacklistService,
    ) {}

    public function checkAndInterceptSurvey(SurveyInterface|SurveyReminderInterface $survey): SurveyInterface|SurveyReminderInterface
    {
        if (!$survey->getInvitationAddress()) {
            return $survey;
        }

        $survey->setTransientNotificationInterception($this->findInterception($survey));

        return $survey;
    }

    public function getAllEmailsForSurvey(
        SurveyInterface|SurveyReminderInterface|SurveyManualReminderInterface $survey
    ): array {
        $this->checkAndInterceptSurvey($survey);
        $emails = $survey->getArrayOfInvitationEmails();
        $interceptionEmails = $survey->getTransientNotificationInterception()?->getAggregatedEmailsArray(get_class($survey));
        foreach ($interceptionEmails ?? [] as $email) {
            $emails[] = $email;
        }

        // Filter out blacklisted emails
        return $this->emailBlacklistService->filterBlacklistedEmails($emails);
    }

    protected function findInterception(SurveyInterface|SurveyReminderInterface $survey): ?NotificationInterception
    {
        $businessName = match(get_class($survey)) {
            DomesticSurvey::class => $survey->getInvitationAddress()->getLine1(),
            InternationalSurvey::class => $survey->getCompany()->getBusinessName(),
            default => throw new \InvalidArgumentException(sprintf('Unexpected survey class "%s"', get_class($survey))),
        };
        if (!$businessName) {
            return null;
        }

        return $this->notificationInterceptionRepository->findOneByBusinessName($businessName);
    }
}