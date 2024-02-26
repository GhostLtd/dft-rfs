<?php

namespace App\Utility\Reminder;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyManualReminderInterface;
use App\Entity\SurveyStateInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Repository\AuditLog\AuditLogRepository;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\AuditEntityLogger\SurveyStateLogger;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

class ManualReminderHelper
{
    protected const REMINDER_MAP = [
        DomesticSurvey::class => Reference::EMAIL_DOMESTIC_SURVEY_MANUAL_REMINDER,
        InternationalSurvey::class => Reference::EMAIL_INTERNATIONAL_SURVEY_MANUAL_REMINDER,
        PreEnquiry::class => null,
    ];

    public function __construct(
        protected AuditLogRepository     $logRepository,
        protected EntityManagerInterface $entityManager,
        protected MessageBusInterface    $messageBus,
        protected PersonalisationHelper  $personalisationHelper,
        protected Security               $security
    ) {}

    public function sendManualReminder(SurveyManualReminderInterface $survey): void
    {
        if (!$this->canSendManualReminder($survey)) {
            return;
        }

        $templateId = $this->getNotifyTemplateId($survey);

        foreach ($this->getRecipientEmails($survey) as $email) {
            $this->messageBus->dispatch(new Email(
                Reference::EVENT_MANUAL_REMINDER,
                $survey::class,
                $survey->getId(),
                $email,
                $templateId,
                $this->personalisationHelper->getForEntity($survey),
            ));
        }

        $username = $this->security->getUser()->getUserIdentifier();

        $log = (new AuditLog())
            ->setEntityClass($survey::class)
            ->setEntityId($survey->getId())
            ->setTimestamp(new \DateTime())
            ->setUsername($username)
            ->setCategory('manual-reminder')
            ->setData([]);

        $this->entityManager->persist($log);

        $survey->setLatestManualReminderSentDate(new \DateTime());

        $note = $survey->createNote()
                ->setNote("Manual reminder sent")
                ->setWasChased(true);

        $survey->addNote($note);

        $this->entityManager->persist($note);
        $this->entityManager->flush();
    }

    public function canSendManualReminder(SurveyManualReminderInterface $survey): bool
    {
        return $this->getReasonWhyCannotSendManualReminder($survey) === null;
    }

    public function canShowManualReminderButton(SurveyManualReminderInterface $survey): bool
    {
        $hasTemplate = isset(self::REMINDER_MAP[$survey::class]);
        return $survey->getState() === SurveyStateInterface::STATE_IN_PROGRESS && $hasTemplate;
    }

    public function getReasonWhyCannotSendManualReminder(SurveyManualReminderInterface $survey): ?UnavailabilityReason
    {
        if ($this->getNotifyTemplateId($survey) === null) {
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
            'manual_reminder' => $survey->getLatestManualReminderSentDate(),
            'in_progress' => $this->getInProgressDate($survey),
            'was_chased' => $this->getLastChasedDate($survey),
        ];

        $latestEventDate = max($eventDates);

        if (!$latestEventDate) {
            return [null, null];
        }

        $latestEvents = array_keys($eventDates, $latestEventDate);
        $eventType = $latestEvents[0];

        if ($eventType === 'was_chased' && array_key_exists('manual_reminder', $latestEvents)) {
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
            $recipients = $survey->getArrayOfInvitationEmails();
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

    protected function getNotifyTemplateId(SurveyManualReminderInterface $survey): ?string
    {
        return self::REMINDER_MAP[$survey::class] ?? null;
    }
}
