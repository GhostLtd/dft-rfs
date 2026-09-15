<?php

namespace App\Entity;

use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Shared mapping for one send of one notification type over one channel.
 * Concrete classes add the ManyToOne to their survey type and use IdTrait.
 */
trait SurveyNotificationTrait
{
    use IdTrait;

    #[ORM\Column(type: Types::STRING, length: 32, enumType: NotificationType::class)]
    private NotificationType $type;

    #[ORM\Column(type: Types::STRING, length: 8, enumType: NotificationChannel::class)]
    private NotificationChannel $channel;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $sentAt;

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function setType(NotificationType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getChannel(): NotificationChannel
    {
        return $this->channel;
    }

    public function setChannel(NotificationChannel $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    public function getSentAt(): \DateTime
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTime $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }
}
