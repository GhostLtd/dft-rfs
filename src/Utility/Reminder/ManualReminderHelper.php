<?php

namespace App\Utility\Reminder;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyNote as DomesticSurveyNote;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Repository\AuditLog\AuditLogRepository;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\AuditEntityLogger\SurveyStateLogger;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Security;

class ManualReminderHelper
{
    protected AuditLogRepository $logRepository;
    protected EntityManagerInterface $entityManager;
    protected MessageBusInterface $messageBus;
    protected PersonalisationHelper $personalisationHelper;
    protected Security $security;

    protected const REMINDER_MAP = [
        DomesticSurvey::class => Reference::EMAIL_DOMESTIC_SURVEY_MANUAL_REMINDER,
        InternationalSurvey::class => Reference::EMAIL_INTERNATIONAL_SURVEY_MANUAL_REMINDER,
        PreEnquiry::class => null,
    ];

    public function __construct(
        AuditLogRepository $logRepository,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
        PersonalisationHelper $personalisationHelper,
        Security $security,
    ) {
        $this->logRepository = $logRepository;
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->personalisationHelper = $personalisationHelper;
        $this->security = $security;
    }

    public function sendManualReminder(SurveyInterface $survey): void
    {
        if (!$this->canSendManualReminder($survey)) {
            return;
        }

        $templateId = $this->getNotifyTemplateId($survey);

        foreach($this->getRecipientEmails($survey) as $email) {
            $this->messageBus->dispatch(new Email(
                Reference::EVENT_MANUAL_REMINDER,
                get_class($survey),
                $survey->getId(),
                $email,
                $templateId,
                $this->personalisationHelper->getForEntity($survey),
            ));
        }

        $username = $this->security->getUser()->getUserIdentifier();

        $log = (new AuditLog())
            ->setEntityClass(get_class($survey))
            ->setEntityId($survey->getId())
            ->setTimestamp(new \DateTime())
            ->setUsername($username)
            ->setCategory('manual-reminder')
            ->setData([]);

        $this->entityManager->persist($log);

        $survey->setLatestManualReminderSentDate(new \DateTime());

        if ($survey instanceof DomesticSurvey) {
            $note = (new DomesticSurveyNote())
                ->setNote("Manual reminder sent")
                ->setWasChased(true);

            $survey->addNote($note);
            $this->entityManager->persist($note);
        }

        $this->entityManager->flush();
    }

    public function canSendManualReminder(SurveyInterface $survey): bool
    {
        return $this->getReasonWhyCannotSendManualReminder($survey) === null;
    }

    public function canShowManualReminderButton(SurveyInterface $survey): bool
    {
        $hasTemplate = isset(self::REMINDER_MAP[get_class($survey)]);
        return $survey->getState() === SurveyInterface::STATE_IN_PROGRESS && $hasTemplate;
    }

    public function getReasonWhyCannotSendManualReminder(SurveyInterface $survey): ?UnavailabilityReason
    {
        if ($this->getNotifyTemplateId($survey) === null) {
            return new UnavailabilityReason(UnavailabilityReason::NO_NOTIFY_TEMPLATE);
        }

        if ($survey->getState() !== SurveyInterface::STATE_IN_PROGRESS) {
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
    public function getMostRecentEventDate(SurveyInterface $survey): array
    {
        $eventDates = [
            'invitation' => $survey->getInvitationSentDate(),
            'first_reminder' => $survey->getFirstReminderSentDate(),
            'second_reminder' => $survey->getSecondReminderSentDate(),
            'manual_reminder' => $survey->getLatestManualReminderSentDate(),
            'in_progress' => $this->getInProgressDate($survey),
        ];

        $latestEventDate = max($eventDates);

        if (!$latestEventDate) {
            return [null, null];
        }

        $eventType = array_keys($eventDates, $latestEventDate)[0];
        return [$latestEventDate, $eventType];
    }

    public function getRecipientEmails(SurveyInterface $survey): ?array
    {
        if ($survey instanceof DomesticSurvey || $survey instanceof InternationalSurvey) {
            $response = $survey->getResponse();
        } elseif ($survey instanceof PreEnquiry) {
            $response = $survey->getResponse();
        } else {
            throw new \RuntimeException('Unsupported survey type: ' . get_class($survey));
        }

        $recipients = $response->getContactEmail() ?? $survey->getInvitationEmails();

        if (!$recipients) {
            return null;
        }

        $recipients = explode(',', $recipients);
        $recipients = array_map(fn(string $recipient) => trim($recipient), $recipients);
        $recipients = array_filter($recipients, fn(string $recipient) => $recipient !== '');
        $recipients = array_values(array_unique($recipients));

        return empty($recipients) ? null : $recipients;
    }

    protected function getInProgressDate(SurveyInterface $survey): ?\DateTime
    {
        /** @var AuditLog[] $logs */
        $logs = $this->logRepository->getLogs($survey->getId(), get_class($survey));
        foreach($logs as $log) {

            $data = $log->getData();
            if (
                $log->getCategory() === SurveyStateLogger::CATEGORY &&
                ($data['to'] ?? '') === SurveyInterface::STATE_IN_PROGRESS
            ) {
                return $log->getTimestamp();
            }
        }

        return null;
    }

    protected function getNotifyTemplateId(SurveyInterface $survey): ?string
    {
        return self::REMINDER_MAP[get_class($survey)] ?? null;
    }
}