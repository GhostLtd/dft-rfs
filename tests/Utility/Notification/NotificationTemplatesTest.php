<?php

namespace App\Tests\Utility\Notification;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationTemplates;
use App\Utility\Notification\NotificationType;
use PHPUnit\Framework\TestCase;

class NotificationTemplatesTest extends TestCase
{
    public function testDomesticHasEveryTypeOnBothChannelsExceptManualReminder(): void
    {
        self::assertSame([
            NotificationChannel::LETTER->value => Reference::LETTER_DOMESTIC_SURVEY_REMINDER_1,
            NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_REMINDER_1,
        ], NotificationTemplates::for(DomesticSurvey::class, NotificationType::REMINDER_1));

        self::assertSame(
            [NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_MANUAL_REMINDER],
            NotificationTemplates::for(DomesticSurvey::class, NotificationType::MANUAL_REMINDER),
        );
    }

    public function testPreEnquiryIsLetterOnlyAndHasNoManualReminder(): void
    {
        foreach ([NotificationType::INVITE, NotificationType::REMINDER_1, NotificationType::REMINDER_2] as $type) {
            self::assertSame(
                [NotificationChannel::LETTER->value],
                array_keys(NotificationTemplates::for(PreEnquiry::class, $type)),
                "PRE {$type->value} is letter-only (EMAIL_PRE_ENQUIRY_* ids are empty in Reference)",
            );
        }
        self::assertFalse(NotificationTemplates::has(PreEnquiry::class, NotificationType::MANUAL_REMINDER));
        self::assertSame([], NotificationTemplates::for(PreEnquiry::class, NotificationType::MANUAL_REMINDER));
    }

    public function testRoRoIsEmailOnlyRemindersOnly(): void
    {
        self::assertSame([NotificationType::REMINDER_1, NotificationType::REMINDER_2], NotificationTemplates::typesFor(RoRoSurvey::class));
        self::assertSame([NotificationChannel::EMAIL->value => Reference::EMAIL_RORO_REMINDER_1], NotificationTemplates::for(RoRoSurvey::class, NotificationType::REMINDER_1));
        self::assertFalse(NotificationTemplates::has(RoRoSurvey::class, NotificationType::INVITE));
    }

    public function testTypesForFollowsEnumOrder(): void
    {
        $all = [NotificationType::INVITE, NotificationType::REMINDER_1, NotificationType::REMINDER_2, NotificationType::MANUAL_REMINDER];
        self::assertSame($all, NotificationTemplates::typesFor(DomesticSurvey::class));
        self::assertSame($all, NotificationTemplates::typesFor(InternationalSurvey::class));
        self::assertSame([NotificationType::INVITE, NotificationType::REMINDER_1, NotificationType::REMINDER_2], NotificationTemplates::typesFor(PreEnquiry::class));
        self::assertSame([], NotificationTemplates::typesFor(\stdClass::class));
    }

    public function testProxyClassNamesResolveLikeTheMappedClass(): void
    {
        $proxy = 'Proxies\\__CG__\\'.DomesticSurvey::class;
        self::assertSame(NotificationTemplates::for(DomesticSurvey::class, NotificationType::REMINDER_1), NotificationTemplates::for($proxy, NotificationType::REMINDER_1));
        self::assertSame(NotificationTemplates::typesFor(DomesticSurvey::class), NotificationTemplates::typesFor($proxy));
        self::assertTrue(NotificationTemplates::has($proxy, NotificationType::MANUAL_REMINDER));
    }

    public function testNoEmptyTemplateIdIsListed(): void
    {
        foreach ([DomesticSurvey::class, InternationalSurvey::class, PreEnquiry::class, RoRoSurvey::class] as $class) {
            foreach (NotificationTemplates::typesFor($class) as $type) {
                foreach (NotificationTemplates::for($class, $type) as $channel => $templateId) {
                    self::assertNotSame('', $templateId, "{$class} {$type->value} {$channel} has an empty template id");
                    self::assertNotNull(NotificationChannel::tryFrom($channel), "{$channel} is not a channel value");
                }
            }
        }
    }
}
