<?php

namespace App\Tests\Utility\Notification;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Entity\SmsRecipientInterface;
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
                "PRE {$type->value} is letter-only (there are no pre-enquiry email templates)",
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
        $notStarted = [NotificationType::INVITE, NotificationType::REMINDER_1, NotificationType::REMINDER_2, NotificationType::MANUAL_REMINDER];
        $inProgress = [NotificationType::IN_PROGRESS_REMINDER_1, NotificationType::IN_PROGRESS_REMINDER_2, NotificationType::IN_PROGRESS_REMINDER_3];
        self::assertSame([...$notStarted, ...$inProgress], NotificationTemplates::typesFor(DomesticSurvey::class));
        self::assertSame($notStarted, NotificationTemplates::typesFor(InternationalSurvey::class));
        self::assertSame([NotificationType::INVITE, NotificationType::REMINDER_1, NotificationType::REMINDER_2], NotificationTemplates::typesFor(PreEnquiry::class));
        self::assertSame([], NotificationTemplates::typesFor(\stdClass::class));
    }

    public function testDomesticInProgressRemindersAreEmailAndSms(): void
    {
        self::assertSame([
            NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_2,
            NotificationChannel::SMS->value => Reference::SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_2,
        ], NotificationTemplates::for(DomesticSurvey::class, NotificationType::IN_PROGRESS_REMINDER_2));
        self::assertFalse(NotificationTemplates::has(InternationalSurvey::class, NotificationType::IN_PROGRESS_REMINDER_1));
    }

    /** NotificationSender throws for an SMS template on a class that can't supply a number; this keeps that unreachable. */
    public function testEveryClassWithAnSmsTemplateCanSupplyANumber(): void
    {
        foreach ([DomesticSurvey::class, InternationalSurvey::class, PreEnquiry::class, RoRoSurvey::class] as $class) {
            foreach (NotificationTemplates::typesFor($class) as $type) {
                if (array_key_exists(NotificationChannel::SMS->value, NotificationTemplates::for($class, $type))) {
                    self::assertTrue(is_a($class, SmsRecipientInterface::class, true), "{$class} has an SMS template for {$type->value} but isn't an SmsRecipientInterface");
                }
            }
        }
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
