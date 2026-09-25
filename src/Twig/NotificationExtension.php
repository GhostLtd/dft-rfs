<?php

namespace App\Twig;

use App\Entity\SurveyNotificationInterface;
use App\Entity\SurveyNotificationsInterface;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationTemplates;
use App\Utility\Notification\NotificationType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('notification_sent_at', $this->latestSentAt(...)),
            new TwigFunction('notification_types_for', $this->groupNotifications(...)),
        ];
    }

    /**
     * Returns the latest notification date for this type, or null when none exists.
     */
    public function latestSentAt(SurveyNotificationsInterface $survey, string $type): ?\DateTime
    {
        return $survey->getLatestNotificationSentAt(NotificationType::from($type));
    }

    /**
     * Returns each supported type and its labelled notifications, newest first.
     * When $showEmails is false, only letter rows are included.
     *
     * @return list<array{type: NotificationType, label: string, rows: list<array{notification: SurveyNotificationInterface, label: string}>}>
     */
    public function groupNotifications(SurveyNotificationsInterface $survey, bool $showEmails = true): array
    {
        $out = [];
        foreach (NotificationTemplates::typesFor($survey::class) as $type) {
            $rows = array_values(array_filter(
                $survey->getNotificationsOfType($type),
                fn(SurveyNotificationInterface $n) => $showEmails || $n->getChannel() === NotificationChannel::LETTER,
            ));
            $out[] = ['type' => $type, 'label' => $type->label(), 'rows' => $this->labelNotifications($rows)];
        }
        return $out;
    }

    /**
     * Returns each notification with its channel label. Repeated sends are numbered per channel.
     *
     * @param list<SurveyNotificationInterface> $rows newest first
     * @return list<array{notification: SurveyNotificationInterface, label: string}>
     */
    private function labelNotifications(array $rows): array
    {
        $countPerChannel = array_count_values(array_map(fn(SurveyNotificationInterface $n) => $n->getChannel()->value, $rows));
        $nextNumber = $countPerChannel; // newest first, so count down

        $out = [];
        foreach ($rows as $notification) {
            $channelEnum = $notification->getChannel();
            $channel = $channelEnum->value;
            $label = $channelEnum->label().($countPerChannel[$channel] > 1 ? " #{$nextNumber[$channel]}" : '');
            $nextNumber[$channel]--;
            $out[] = ['notification' => $notification, 'label' => $label];
        }
        return $out;
    }
}
