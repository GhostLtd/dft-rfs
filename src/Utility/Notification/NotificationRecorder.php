<?php

namespace App\Utility\Notification;

use App\Entity\SurveyNotificationInterface;
use App\Entity\SurveyNotificationsInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates a notification row and attaches it to its survey. The caller flushes the entity manager.
 */
class NotificationRecorder
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /**
     * Creates, attaches and persists a notification row, then returns it without flushing.
     */
    public function record(SurveyNotificationsInterface $survey, NotificationType $type, NotificationChannel $channel, \DateTime $sentAt): SurveyNotificationInterface
    {
        $notification = $survey->createNotification()
            ->setType($type)
            ->setChannel($channel)
            ->setSentAt($sentAt);

        $survey->addNotification($notification);
        $this->entityManager->persist($notification);

        return $notification;
    }
}
