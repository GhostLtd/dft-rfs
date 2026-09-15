<?php

namespace App\Entity;

use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\Common\Collections\Collection;

interface SurveyNotificationsInterface
{
    public function getId(): ?string;

    /** @return Collection<int, SurveyNotificationInterface> */
    public function getNotifications(): Collection;
    public function createNotification(): SurveyNotificationInterface;
    public function addNotification(SurveyNotificationInterface $notification): static;

    /** @return array<SurveyNotificationInterface> newest first */
    public function getNotificationsOfType(NotificationType $type, ?NotificationChannel $channel = null): array;
    public function getLatestNotificationSentAt(NotificationType $type, ?NotificationChannel $channel = null): ?\DateTime;
    public function getFirstNotificationSentAt(NotificationType $type): ?\DateTime;
    public function hasNotification(NotificationType $type): bool;

    /** @return class-string<SurveyNotificationInterface> */
    public static function getNotificationClass(): string;
}
