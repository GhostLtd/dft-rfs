<?php

namespace App\Tests\Utility\Notification;

use App\Utility\Notification\NotificationType;
use PHPUnit\Framework\TestCase;

class NotificationEnumsTest extends TestCase
{
    public function testTypeValuesArePinned(): void
    {
        $this->assertValuePinned('invite', NotificationType::INVITE);
        $this->assertValuePinned('reminder-1', NotificationType::REMINDER_1);
        $this->assertValuePinned('reminder-2', NotificationType::REMINDER_2);
        $this->assertValuePinned('reminder-manual', NotificationType::MANUAL_REMINDER);
    }

    // These values are persisted (event/log names, DB rows): routed through a plain-`string`
    // parameter so PHPStan compares two non-literal types and doesn't flag the check as
    // tautological, which it would for a direct assertSame() against the enum's literal value.
    private function assertValuePinned(string $expected, NotificationType $type): void
    {
        self::assertSame($expected, $type->value);
    }

    public function testTypeValuesFitEventNameColumn(): void
    {
        foreach (NotificationType::cases() as $type) {
            self::assertLessThanOrEqual(32, strlen($type->value));
        }
    }
}
