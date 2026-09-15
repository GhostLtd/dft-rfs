<?php

namespace App\Tests\Twig;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Twig\NotificationExtension;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use PHPUnit\Framework\TestCase;

class NotificationExtensionTest extends TestCase
{
    public function testSentAtReturnsLatestOfType(): void
    {
        $survey = new Survey();
        foreach (['2026-01-01', '2026-02-01'] as $d) {
            $survey->addNotification((new SurveyNotification())->setType(NotificationType::REMINDER_1)->setChannel(NotificationChannel::LETTER)->setSentAt(new \DateTime($d)));
        }
        $ext = new NotificationExtension();
        self::assertEquals(new \DateTime('2026-02-01'), $ext->sentAt($survey, 'reminder-1'));
        self::assertNull($ext->sentAt($survey, 'reminder-2'));
    }

    public function testUnknownTypeThrows(): void
    {
        $this->expectException(\ValueError::class);
        (new NotificationExtension())->sentAt(new Survey(), 'nope');
    }

    public function testTypesForLabelsRowsWithinTheirOwnChannel(): void
    {
        $survey = new Survey();
        foreach ([['2026-01-01', NotificationChannel::LETTER], ['2026-01-01', NotificationChannel::EMAIL], ['2026-02-01', NotificationChannel::LETTER]] as [$d, $channel]) {
            $survey->addNotification((new SurveyNotification())->setType(NotificationType::INVITE)->setChannel($channel)->setSentAt(new \DateTime($d)));
        }
        $ext = new NotificationExtension();

        $labels = fn(bool $showEmails) => array_map(fn(array $row) => $row['label'], $ext->typesFor($survey, $showEmails)[0]['rows']);
        self::assertSame(['Letter #2', 'Letter #1', 'Email'], $labels(true), 'newest first; only the resent channel is numbered');
        self::assertSame(['Letter #2', 'Letter #1'], $labels(false));
    }

    public function testTypesForReturnsTemplateTableOrderPerSurveyClass(): void
    {
        $ext = new NotificationExtension();

        $csrgtTypes = array_map(fn(array $group) => $group['type'], $ext->typesFor(new Survey()));
        self::assertEquals(
            [NotificationType::INVITE, NotificationType::REMINDER_1, NotificationType::REMINDER_2, NotificationType::MANUAL_REMINDER],
            $csrgtTypes
        );

        $preEnquiryTypes = array_map(fn(array $group) => $group['type'], $ext->typesFor(new PreEnquiry()));
        self::assertEquals(
            [NotificationType::INVITE, NotificationType::REMINDER_1, NotificationType::REMINDER_2],
            $preEnquiryTypes
        );
    }
}
