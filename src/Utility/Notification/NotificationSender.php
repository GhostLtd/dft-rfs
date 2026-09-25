<?php

namespace App\Utility\Notification;

use App\Entity\Embeddable\LongAddress;
use App\Entity\SmsRecipientInterface;
use App\Entity\SurveyInterface;
use App\Entity\SurveyNotificationsInterface;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\EmailBlacklistService;
use App\Utility\NotificationInterceptionService;
use App\Utility\UkMobileNumber;
use Doctrine\Common\Util\ClassUtils;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\AbstractMessage;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Builds and previews survey notifications, and records and queues messages for delivery.
 * The caller manages the transaction and flushes the entity manager.
 */
class NotificationSender
{
    private const string LOG_PREFIX = 'Notifications: ';

    public function __construct(
        private readonly NotificationRecorder $recorder,
        private readonly MessageBusInterface $messageBus,
        private readonly PersonalisationHelper $personalisationHelper,
        private readonly NotificationInterceptionService $notificationInterceptionService,
        private readonly EmailBlacklistService $emailBlacklistService,
        private readonly SmsAllowance $smsAllowance,
        private readonly SmsRecipientDailyCap $dailyCap,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Records notification rows and queues messages for a survey: one row per channel sent, and one message per
     * recipient carrying that row's ID. Returns the channels queued, or an empty list if none were queued.
     *
     * Charges an SMS to the per-number daily cap, then the annual allowance, before recording it; a refusal skips
     * the SMS. $delay schedules messages for later.
     * The caller must manage the transaction and flush the entity manager.
     *
     * @return list<NotificationChannel>
     */
    public function send(SurveyInterface&SurveyNotificationsInterface $survey, NotificationType $type, ?\DateInterval $delay = null): array
    {
        $now = \DateTime::createFromInterface($this->clock->now());
        $sentAt = clone $now;
        $stamps = [];
        if ($delay !== null) {
            $sentAt->add($delay);
            $stamps[] = new DelayStamp(($sentAt->getTimestamp() - $now->getTimestamp()) * 1000);
        }

        $channels = $this->selectRecipients($survey, $type);
        $personalisation = $this->personalisationHelper->getForEntity($survey);
        $sent = [];
        $messages = [];

        foreach ($channels as [$channel, $template, $recipients, $reason]) {
            if ($recipients === []) {
                $this->logger->info(self::LOG_PREFIX."Skipped {$type->value} via {$channel->value} for {$survey->getId()}: {$reason}");
                continue;
            }

            if ($channel === NotificationChannel::SMS) {
                if (!$this->dailyCap->tryConsume($recipients[0], $sentAt)) {
                    $this->logger->info(self::LOG_PREFIX."Skipped {$type->value} via sms for {$survey->getId()}: per-number daily cap reached");
                    continue;
                }
                if (!$this->smsAllowance->tryConsume($sentAt)) {
                    $this->logger->warning(self::LOG_PREFIX."SMS allowance exhausted for FY {$this->smsAllowance->financialYear($sentAt)}; skipped {$type->value} via sms for {$survey->getId()}");
                    continue;
                }
            }

            $notification = $this->recorder->record($survey, $type, $channel, clone $sentAt);
            if ($channel === NotificationChannel::SMS) {
                $this->smsAllowance->recordReservation($notification->getId() ?? throw new \LogicException('notification row has no id after persist'), $sentAt);
            }
            $sent[] = $channel;

            array_push($messages, ...$this->createMessages($survey, $type, $channel, $template, $recipients, $personalisation, $notification->getId()));
        }

        foreach ($messages as $message) {
            $this->messageBus->dispatch($message, $stamps);
        }

        if ($sent !== []) {
            $this->logger->info(self::LOG_PREFIX."Sent {$type->value} via ".join(', ', array_map(fn($c) => $c->value, $sent))." for {$survey->getId()}");
        }

        return $sent;
    }

    /**
     * Returns a preview containing:
     * - messages: Email, Sms and Letter objects that would be sent, with "dry-run" as their notification ID.
     * - skipped: channels excluded and the reasons for excluding them.
     *
     * Does not record notifications, charge either SMS limit or dispatch messages.
     *
     * @param \Closure(string): ?string $smsBudget checks the simulated SMS budget for the normalised mobile:
     *     null allows the SMS; a string gives the reason to skip it
     * @return array{messages: list<AbstractMessage>, skipped: list<array{channel: NotificationChannel, reason: string}>}
     */
    public function preview(SurveyInterface&SurveyNotificationsInterface $survey, NotificationType $type, \Closure $smsBudget): array
    {
        $channels = $this->selectRecipients($survey, $type);
        $personalisation = $this->personalisationHelper->getForEntity($survey);
        $messages = [];
        $skipped = [];

        foreach ($channels as [$channel, $template, $recipients, $reason]) {
            if ($recipients === []) {
                $skipped[] = ['channel' => $channel, 'reason' => $reason];
                continue;
            }
            if ($channel === NotificationChannel::SMS && ($budgetReason = $smsBudget($recipients[0])) !== null) {
                $skipped[] = ['channel' => $channel, 'reason' => $budgetReason];
                continue;
            }

            array_push($messages, ...$this->createMessages($survey, $type, $channel, $template, $recipients, $personalisation, 'dry-run'));
        }

        return ['messages' => $messages, 'skipped' => $skipped];
    }

    /**
     * Returns [channel, template ID, recipients, skip reason] for each channel the type has a template for, in
     * template order. Recipients is empty, with the reason, when the channel has none.
     * Throws a LogicException when the type does not apply to the survey's class.
     *
     * @return list<array{0: NotificationChannel, 1: string, 2: list<mixed>, 3: string}>
     */
    private function selectRecipients(SurveyInterface $survey, NotificationType $type): array
    {
        $surveyClass = $survey::class;
        $templates = NotificationTemplates::for($surveyClass, $type);
        if ($templates === []) {
            throw new \LogicException("{$type->value} does not apply to {$surveyClass}");
        }

        $channels = [];
        foreach ($templates as $channelValue => $template) {
            $channel = NotificationChannel::from($channelValue);
            [$recipients, $reason] = match ($channel) {
                NotificationChannel::LETTER => $this->getLetterRecipient($survey),
                NotificationChannel::EMAIL => $this->getEmailRecipients($survey, $type),
                NotificationChannel::SMS => $this->getSmsRecipient($survey),
            };
            $channels[] = [$channel, $template, $recipients, $reason];
        }

        return $channels;
    }

    /**
     * Returns one message per recipient, each with the template, personalisation, notification ID and Notify reference.
     *
     * @param list<mixed> $recipients
     * @return list<AbstractMessage>
     */
    private function createMessages(SurveyInterface&SurveyNotificationsInterface $survey, NotificationType $type, NotificationChannel $channel, string $template, array $recipients, array $personalisation, ?string $notificationId): array
    {
        $notificationClass = $survey::getNotificationClass();
        $messages = [];
        foreach ($recipients as $recipient) {
            $message = match ($channel) {
                NotificationChannel::LETTER => new Letter($type->value, $notificationClass, $notificationId, $recipient, $template, $personalisation),
                NotificationChannel::EMAIL => new Email($type->value, $notificationClass, $notificationId, $recipient, $template, $personalisation),
                NotificationChannel::SMS => new Sms($type->value, $notificationClass, $notificationId, $recipient, $template, $personalisation),
            };
            $message->setReference($this->buildSurveyReference($survey, $message));
            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Returns [addresses, skip reason], with one invitation address or an empty list.
     * The reason is empty when the address is usable.
     *
     * @return array{0: list<LongAddress>, 1: string}
     */
    private function getLetterRecipient(SurveyInterface $survey): array
    {
        $address = $survey->getInvitationAddress();

        return $address instanceof LongAddress && $address->isFilled() ? [[$address], ''] : [[], 'no address'];
    }

    /**
     * Returns [email addresses, skip reason], after applying the email blacklist.
     * Manual and in-progress reminders prefer the response contact; other notifications use invitation emails.
     * The reason is empty when at least one address remains.
     *
     * @return array{0: list<string>, 1: string}
     */
    private function getEmailRecipients(SurveyInterface $survey, NotificationType $type): array
    {
        $emails = match ($type) {
            // The response contact is the person filling the survey in; fall back to the invitation emails.
            NotificationType::MANUAL_REMINDER,
            NotificationType::IN_PROGRESS_REMINDER_1,
            NotificationType::IN_PROGRESS_REMINDER_2,
            NotificationType::IN_PROGRESS_REMINDER_3 => $survey->getResponseContactEmail()
                ? [$survey->getResponseContactEmail()]
                : $this->notificationInterceptionService->getAllEmailsForSurvey($survey),
            default => $this->notificationInterceptionService->getAllEmailsForSurvey($survey),
        };

        // Apply the blacklist to response contacts as well as invitation emails.
        $emails = $this->emailBlacklistService->filterBlacklistedEmails(array_values($emails));

        return $emails === [] ? [[], 'no email recipients'] : [$emails, ''];
    }

    /**
     * Returns [mobile numbers, skip reason], with one normalised UK mobile or an empty list.
     * The reason explains a missing or invalid number, and is empty for a valid mobile.
     *
     * @return array{0: list<string>, 1: string}
     */
    private function getSmsRecipient(SurveyInterface $survey): array
    {
        if (!$survey instanceof SmsRecipientInterface) {
            throw new \LogicException(ClassUtils::getRealClass($survey::class).' has an SMS template but is not an '.SmsRecipientInterface::class);
        }

        $telephone = $survey->getSmsRecipientTelephone();
        if ($telephone === null || trim($telephone) === '') {
            return [[], 'no telephone'];
        }
        $mobile = UkMobileNumber::normalise($telephone);

        return $mobile === null ? [[], 'not a UK mobile'] : [[$mobile], ''];
    }

    /**
     * Returns the Notify reference: "{survey class}::{survey ID}::{recipient hash}".
     * Omits the recipient hash and its separator when the message has no hash.
     */
    private function buildSurveyReference(SurveyInterface $survey, AbstractMessage $message): string
    {
        $reference = ClassUtils::getRealClass($survey::class).'::'.$survey->getId();
        $hash = $message->getRecipientHash();

        return $hash === null ? $reference : "{$reference}::{$hash}";
    }
}
