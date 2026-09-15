<?php

namespace App\Tests\Entity;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\SurveyNotificationInterface;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use PHPUnit\Framework\TestCase;

class SurveyNotificationsTraitTest extends TestCase
{
    private function add(Survey $survey, NotificationType $type, NotificationChannel $channel, string $sentAt): void
    {
        $survey->addNotification(
            (new SurveyNotification())->setType($type)->setChannel($channel)->setSentAt(new \DateTime($sentAt))
        );
    }

    public function testLatestAndFirstAcrossRepeats(): void
    {
        $survey = new Survey();
        $this->add($survey, NotificationType::INVITE, NotificationChannel::LETTER, '2026-01-01 10:00');
        $this->add($survey, NotificationType::INVITE, NotificationChannel::EMAIL, '2026-01-01 10:00');
        $this->add($survey, NotificationType::INVITE, NotificationChannel::LETTER, '2026-02-01 10:00');
        $this->add($survey, NotificationType::REMINDER_1, NotificationChannel::LETTER, '2026-01-09 10:00');

        self::assertEquals(new \DateTime('2026-02-01 10:00'), $survey->getLatestNotificationSentAt(NotificationType::INVITE));
        self::assertEquals(new \DateTime('2026-01-01 10:00'), $survey->getFirstNotificationSentAt(NotificationType::INVITE));
        self::assertEquals(new \DateTime('2026-01-01 10:00'), $survey->getLatestNotificationSentAt(NotificationType::INVITE, NotificationChannel::EMAIL));
        self::assertNull($survey->getLatestNotificationSentAt(NotificationType::REMINDER_2));
        self::assertTrue($survey->hasNotification(NotificationType::REMINDER_1));
        self::assertFalse($survey->hasNotification(NotificationType::MANUAL_REMINDER));
    }

    public function testNotificationsOfTypeNewestFirst(): void
    {
        $survey = new Survey();
        $this->add($survey, NotificationType::MANUAL_REMINDER, NotificationChannel::EMAIL, '2026-01-01');
        $this->add($survey, NotificationType::MANUAL_REMINDER, NotificationChannel::EMAIL, '2026-03-01');
        $this->add($survey, NotificationType::MANUAL_REMINDER, NotificationChannel::EMAIL, '2026-02-01');

        $dates = array_map(fn(SurveyNotificationInterface $n) => $n->getSentAt()->format('Y-m-d'), $survey->getNotificationsOfType(NotificationType::MANUAL_REMINDER));
        self::assertSame(['2026-03-01', '2026-02-01', '2026-01-01'], $dates);
    }

    public function testAddSetsBackReferenceAndRejectsWrongClass(): void
    {
        $survey = new Survey();
        $notification = $survey->createNotification();
        self::assertInstanceOf(SurveyNotification::class, $notification);
        $survey->addNotification($notification->setType(NotificationType::INVITE)->setChannel(NotificationChannel::LETTER)->setSentAt(new \DateTime()));
        self::assertSame($survey, $notification->getSurvey());

        $this->expectException(\RuntimeException::class);
        $survey->addNotification(new \App\Entity\International\SurveyNotification());
    }
}
