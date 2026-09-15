<?php

namespace App\Utility\Notification;

use App\Entity\SurveyNotificationInterface;
use App\Entity\SurveyNotificationsInterface;
use Doctrine\ORM\EntityManagerInterface;

/** Appends one notification row. Never flushes: the caller owns the transaction. */
class NotificationRecorder
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

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
