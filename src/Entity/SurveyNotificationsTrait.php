<?php

namespace App\Entity;

use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\Common\Collections\Collection;

/**
 * Stores and queries a survey's notifications.
 * Requires a mapped notifications Collection, createNotification() and getNotificationClass().
 */
trait SurveyNotificationsTrait
{
    /** @return Collection<int, SurveyNotificationInterface> */
    public function getNotifications(): Collection
    {
        // Concrete notification collections satisfy the shared interface at runtime.
        // @phpstan-ignore return.type (invariant generics: concrete Collection<T> vs shared interface return type)
        return $this->notifications;
    }

    public function addNotification(SurveyNotificationInterface $notification): static
    {
        $expectedClass = static::getNotificationClass();
        if (!$notification instanceof $expectedClass) {
            throw new \RuntimeException("Got a ".$notification::class.", but expected a ".$expectedClass);
        }

        if (!$this->notifications->contains($notification)) {
            $notification->setSurvey($this);
            // The runtime instanceof check above validates the concrete collection type.
            // @phpstan-ignore offsetAssign.valueType (verified by the instanceof check above)
            $this->notifications[] = $notification;
        }

        return $this;
    }

    /**
     * Returns matching notifications newest first, optionally restricted to one channel.
     *
     * @return array<SurveyNotificationInterface>
     */
    public function getNotificationsOfType(NotificationType $type, ?NotificationChannel $channel = null): array
    {
        $matching = $this->notifications
            ->filter(fn(SurveyNotificationInterface $n) =>
                $n->getType() === $type && ($channel === null || $n->getChannel() === $channel)
            )
            ->toArray();

        usort($matching, fn(SurveyNotificationInterface $a, SurveyNotificationInterface $b) => $b->getSentAt() <=> $a->getSentAt());

        return $matching;
    }

    /**
     * Returns the newest matching send date, or null when no notification matches.
     */
    public function getLatestNotificationSentAt(NotificationType $type, ?NotificationChannel $channel = null): ?\DateTime
    {
        $matching = $this->getNotificationsOfType($type, $channel);
        return $matching === [] ? null : $matching[0]->getSentAt();
    }

    /**
     * Returns the earliest send date for this type, or null when none exists.
     */
    public function getFirstNotificationSentAt(NotificationType $type): ?\DateTime
    {
        $matching = $this->getNotificationsOfType($type);
        return $matching === [] ? null : end($matching)->getSentAt();
    }

    public function hasNotification(NotificationType $type): bool
    {
        return $this->getNotificationsOfType($type) !== [];
    }
}
