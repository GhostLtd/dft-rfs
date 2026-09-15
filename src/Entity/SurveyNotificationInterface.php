<?php

namespace App\Entity;

use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;

interface SurveyNotificationInterface
{
    public function getId(): ?string;
    public function getType(): NotificationType;
    public function setType(NotificationType $type): static;
    public function getChannel(): NotificationChannel;
    public function setChannel(NotificationChannel $channel): static;
    public function getSentAt(): \DateTime;
    public function setSentAt(\DateTime $sentAt): static;
    public function getSurvey(): ?SurveyNotificationsInterface;
    public function setSurvey(SurveyNotificationsInterface $survey): static;
}
