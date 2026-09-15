<?php

namespace App\Entity;

use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\Common\Collections\Collection;

/**
 * Requires the using class to declare `private Collection $notifications`
 * (OneToMany to its notification class, initialised in the constructor)
 * and to implement createNotification() and getNotificationClass().
 */
trait SurveyNotificationsTrait
{
    /** @return Collection<int, SurveyNotificationInterface> */
    public function getNotifications(): Collection
    {
        // Each using class's $notifications is typed to its own concrete notification class for
        // Doctrine's benefit, which PHPStan's invariant generics see as narrower than this shared
        // trait's SurveyNotificationInterface-typed return; the runtime type is correct.
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
            // Just verified above via instanceof against $expectedClass, but PHPStan can't narrow to it
            // since it's a runtime class-string, not a literal class name.
            // @phpstan-ignore offsetAssign.valueType (verified by the instanceof check above)
            $this->notifications[] = $notification;
        }

        return $this;
    }

    /** @return array<SurveyNotificationInterface> */
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

    public function getLatestNotificationSentAt(NotificationType $type, ?NotificationChannel $channel = null): ?\DateTime
    {
        $matching = $this->getNotificationsOfType($type, $channel);
        return $matching === [] ? null : $matching[0]->getSentAt();
    }

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
