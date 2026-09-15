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
            new TwigFunction('notification_sent_at', $this->sentAt(...)),
            new TwigFunction('notification_types_for', $this->typesFor(...)),
        ];
    }

    public function sentAt(SurveyNotificationsInterface $survey, string $type): ?\DateTime
    {
        return $survey->getLatestNotificationSentAt(NotificationType::from($type));
    }

    /**
     * Types relevant to this survey class, in display order, each with its rows newest first. Each row is labelled
     * by channel, numbered within its own channel only when that channel was sent more than once, so a
     * letter+email pair for one invite doesn't read as a resend.
     * @return list<array{type: NotificationType, label: string, rows: list<array{notification: SurveyNotificationInterface, label: string}>}>
     */
    public function typesFor(SurveyNotificationsInterface $survey, bool $showEmails = true): array
    {
        $out = [];
        foreach (NotificationTemplates::typesFor($survey::class) as $type) {
            $rows = array_values(array_filter(
                $survey->getNotificationsOfType($type),
                fn(SurveyNotificationInterface $n) => $showEmails || $n->getChannel() === NotificationChannel::LETTER,
            ));
            $out[] = ['type' => $type, 'label' => $type->label(), 'rows' => $this->labelled($rows)];
        }
        return $out;
    }

    /**
     * @param list<SurveyNotificationInterface> $rows newest first
     * @return list<array{notification: SurveyNotificationInterface, label: string}>
     */
    private function labelled(array $rows): array
    {
        $countPerChannel = array_count_values(array_map(fn(SurveyNotificationInterface $n) => $n->getChannel()->value, $rows));
        $nextNumber = $countPerChannel; // newest first, so count down

        $out = [];
        foreach ($rows as $notification) {
            $channel = $notification->getChannel()->value;
            $label = ucfirst($channel).($countPerChannel[$channel] > 1 ? " #{$nextNumber[$channel]}" : '');
            $nextNumber[$channel]--;
            $out[] = ['notification' => $notification, 'label' => $label];
        }
        return $out;
    }
}
