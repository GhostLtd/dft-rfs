<?php

namespace App\Utility\Notification;

use App\Entity\Embeddable\LongAddress;
use App\Entity\SurveyInterface;
use App\Entity\SurveyNotificationsInterface;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\EmailBlacklistService;
use App\Utility\NotificationInterceptionService;
use Doctrine\Common\Util\ClassUtils;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\AbstractMessage;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Sends one notification of a given type for one survey: resolves recipients from the type, records one
 * notification row per channel actually sent, and dispatches the Notify messages. Never flushes and never
 * opens a transaction -- the caller owns both.
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
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param \DateInterval|null $delay delays the Notify messages (DelayStamp) and stamps the row's sentAt with the scheduled time
     * @return list<NotificationChannel> channels sent
     */
    public function send(SurveyInterface&SurveyNotificationsInterface $survey, NotificationType $type, ?\DateInterval $delay = null): array
    {
        $surveyClass = $survey::class;
        $templates = NotificationTemplates::for($surveyClass, $type);
        if ($templates === []) {
            throw new \LogicException("{$type->value} does not apply to {$surveyClass}");
        }

        $now = \DateTime::createFromInterface($this->clock->now());
        $sentAt = clone $now;
        $stamps = [];
        if ($delay !== null) {
            $sentAt->add($delay);
            $stamps[] = new DelayStamp(($sentAt->getTimestamp() - $now->getTimestamp()) * 1000);
        }

        $personalisation = $this->personalisationHelper->getForEntity($survey);
        $sent = [];

        foreach ($templates as $channelValue => $template) {
            $channel = NotificationChannel::from($channelValue);
            $messages = match ($channel) {
                NotificationChannel::LETTER => $this->letters($survey, $type, $template, $personalisation, $sentAt),
                NotificationChannel::EMAIL => $this->emails($survey, $type, $template, $personalisation, $sentAt),
                NotificationChannel::SMS => [], // no producer yet; arm exists only because the enum has the case
            };

            if ($messages === []) {
                $this->logger->info(self::LOG_PREFIX."Skipped {$type->value} via {$channel->value} for {$survey->getId()}: no recipients");
                continue;
            }

            foreach ($messages as $message) {
                $this->messageBus->dispatch($message, $stamps);
            }
            $sent[] = $channel;
        }

        if ($sent !== []) {
            $this->logger->info(self::LOG_PREFIX."Sent {$type->value} via ".join(', ', array_map(fn($c) => $c->value, $sent))." for {$survey->getId()}");
        }

        return $sent;
    }

    /** @return list<Letter> at most one; records the row only when there is an address to send to */
    private function letters(SurveyInterface&SurveyNotificationsInterface $survey, NotificationType $type, string $template, array $personalisation, \DateTime $sentAt): array
    {
        $address = $survey->getInvitationAddress();
        if (!$address instanceof LongAddress || !$address->isFilled()) {
            return [];
        }

        $notification = $this->recorder->record($survey, $type, NotificationChannel::LETTER, clone $sentAt);

        $letter = new Letter($type->value, $notification::class, $notification->getId(), $address, $template, $personalisation);
        $letter->setReference($this->surveyReference($survey, $letter));

        return [$letter];
    }

    /** @return list<Email> one per recipient; records the row only when there is at least one recipient */
    private function emails(SurveyInterface&SurveyNotificationsInterface $survey, NotificationType $type, string $template, array $personalisation, \DateTime $sentAt): array
    {
        $emails = match ($type) {
            // The response contact is the person filling the survey in; fall back to the invitation emails.
            NotificationType::MANUAL_REMINDER => $survey->getResponseContactEmail()
                ? [$survey->getResponseContactEmail()]
                : $this->notificationInterceptionService->getAllEmailsForSurvey($survey),
            default => $this->notificationInterceptionService->getAllEmailsForSurvey($survey),
        };

        // getAllEmailsForSurvey() already filters blacklisted addresses; the manual-reminder contact branch bypasses it.
        $emails = $this->emailBlacklistService->filterBlacklistedEmails(array_values($emails));
        if ($emails === []) {
            return [];
        }

        $notification = $this->recorder->record($survey, $type, NotificationChannel::EMAIL, clone $sentAt);

        return array_map(function (string $email) use ($survey, $type, $notification, $template, $personalisation): Email {
            $message = new Email($type->value, $notification::class, $notification->getId(), $email, $template, $personalisation);
            $message->setReference($this->surveyReference($survey, $message));
            return $message;
        }, $emails);
    }

    /**
     * The Notify reference the bundle would build if the survey were the originating entity (as it was before
     * notifications became rows): "{survey class}::{survey id}::{recipient hash}". The originating entity is now
     * the notification row, so without this the reference would name the row instead of the survey.
     */
    private function surveyReference(SurveyInterface $survey, AbstractMessage $message): string
    {
        $reference = ClassUtils::getRealClass($survey::class).'::'.$survey->getId();
        $hash = $message->getRecipientHash();

        return $hash === null ? $reference : "{$reference}::{$hash}";
    }
}
