<?php

namespace App\Utility\Reminder;

use App\Entity\AuditLog\AuditLog;
use App\Entity\SurveyInterface;
use App\Entity\SurveyManualReminderInterface;
use App\Entity\SurveyStateInterface;
use App\Repository\AuditLog\AuditLogRepository;
use App\Utility\AuditEntityLogger\SurveyStateLogger;
use App\Utility\Notification\NotificationSender;
use App\Utility\Notification\NotificationTemplates;
use App\Utility\Notification\NotificationType;
use App\Utility\NotificationInterceptionService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ManualReminderHelper
{
    public function __construct(
        protected AuditLogRepository              $logRepository,
        protected EntityManagerInterface          $entityManager,
        protected Security                        $security,
        protected NotificationInterceptionService $notificationInterceptionService,
        protected NotificationSender              $notificationSender,
    ) {}

    public function sendManualReminder(SurveyManualReminderInterface $survey): bool
    {
        if (!$this->canSendManualReminder($survey)) {
            return false;
        }

        // NotificationSender::send() requires SurveyInterface&SurveyNotificationsInterface;
        // every current implementer of SurveyManualReminderInterface satisfies it.
        if (!$survey instanceof SurveyInterface) {
            throw new \LogicException('unhandled survey class: '.$survey::class);
        }

        return $this->entityManager->wrapInTransaction(function () use ($survey): bool {
            $sent = $this->notificationSender->send($survey, NotificationType::MANUAL_REMINDER);
            if ($sent === []) {
                return false;
            }

            $log = (new AuditLog())
                ->setEntityClass($survey::class)
                ->setEntityId($survey->getId())
                ->setTimestamp(new \DateTime())
                ->setUsername($this->security->getUser()->getUserIdentifier())
                ->setCategory('manual-reminder')
                ->setData([]);
            $this->entityManager->persist($log);

            $note = $survey->createNote()->setNote("Manual reminder sent")->setWasChased(true);
            $survey->addNote($note);
            $this->entityManager->persist($note);

            return true;
        });
    }

    public function canSendManualReminder(SurveyManualReminderInterface $survey): bool
    {
        return $this->getReasonWhyCannotSendManualReminder($survey) === null;
    }

    public function canShowManualReminderButton(SurveyManualReminderInterface $survey): bool
    {
        $hasRule = NotificationTemplates::has($survey::class, NotificationType::MANUAL_REMINDER);
        return $survey->getState() === SurveyStateInterface::STATE_IN_PROGRESS && $hasRule;
    }

    public function getReasonWhyCannotSendManualReminder(SurveyManualReminderInterface $survey): ?UnavailabilityReason
    {
        if (!NotificationTemplates::has($survey::class, NotificationType::MANUAL_REMINDER)) {
            return new UnavailabilityReason(UnavailabilityReason::NO_NOTIFY_TEMPLATE);
        }

        if ($survey->getState() !== SurveyStateInterface::STATE_IN_PROGRESS) {
            return new UnavailabilityReason(UnavailabilityReason::NOT_IN_PROGRESS);
        }

        if ($this->getRecipientEmails($survey) === null) {
            return new UnavailabilityReason(UnavailabilityReason::NO_EMAIL_ADDRESSES);
        }

        $sevenDaysAgo = (new \DateTime())->modify('-7 days')->modify('+1 second');
        [$latestEventDate, $eventType] = $this->getMostRecentEventDate($survey);

        return ($latestEventDate <= $sevenDaysAgo) ?
            null :
            new UnavailabilityReason(UnavailabilityReason::TOO_SOON, $eventType, $sevenDaysAgo->diff($latestEventDate));
    }

    /**
     * @return array{0: ?DateTime, 1: ?string}
     */
    public function getMostRecentEventDate(SurveyManualReminderInterface $survey): array
    {
        $eventDates = [
            'manual_reminder' => $survey->getLatestNotificationSentAt(NotificationType::MANUAL_REMINDER),
            'in_progress' => $this->getInProgressDate($survey),
            'was_chased' => $this->getLastChasedDate($survey),
        ];

        $latestEventDate = max($eventDates);

        if (!$latestEventDate) {
            return [null, null];
        }

        $latestEvents = array_keys($eventDates, $latestEventDate);
        $eventType = $latestEvents[0];

        if ($eventType === 'was_chased' && in_array('manual_reminder', $latestEvents, true)) {
            // Given that a manual reminder also adds a note at the same time, we might end up with both,
            // but ultimately want to choose the manual_reminder as the cause in such a case.
            $eventType = 'manual_reminder';
        }

        return [$latestEventDate, $eventType];
    }

    public function getRecipientEmails(SurveyManualReminderInterface $survey): ?array
    {
        $recipients = $survey->getResponseContactEmail();

        if ($recipients) {
            $recipients = [$recipients];
        } else {
            $recipients = $this->notificationInterceptionService->getAllEmailsForSurvey($survey);
        }

        if (empty($recipients)) {
            return null;
        }

        return $recipients;
    }

    protected function getInProgressDate(SurveyManualReminderInterface $survey): ?\DateTime
    {
        /** @var AuditLog[] $logs */
        $logs = $this->logRepository->getLogs($survey->getId(), $survey::class);
        foreach ($logs as $log) {

            $data = $log->getData();
            if (
                $log->getCategory() === SurveyStateLogger::CATEGORY &&
                ($data['to'] ?? '') === SurveyStateInterface::STATE_IN_PROGRESS
            ) {
                return $log->getTimestamp();
            }
        }

        return null;
    }

    protected function getLastChasedDate(SurveyManualReminderInterface $survey): ?\DateTime
    {
        $latestDate = null;

        foreach ($survey->getNotes() as $note) {
            if ($note->getWasChased()) {
                $latestDate = max($latestDate, $note->getCreatedAt());
            }
        }

        return $latestDate;
    }
}
