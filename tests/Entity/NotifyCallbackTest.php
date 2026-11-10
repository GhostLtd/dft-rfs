<?php

namespace App\Tests\Entity;

use App\Entity\NotifyCallback;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NotifyCallbackTest extends TestCase
{
    #[DataProvider('statuses')]
    public function testIsFatal(string $type, string $status, bool $fatal): void
    {
        $callback = (new NotifyCallback())->setType($type)->setStatus($status);
        self::assertSame($fatal, $callback->isFatal());
    }

    public static function statuses(): array
    {
        return [
            'delivered' => [NotifyCallback::TYPE_DELIVERY_RECEIPT, NotifyCallback::STATUS_DELIVERED, false],
            'temporary failure is not fatal' => [NotifyCallback::TYPE_DELIVERY_RECEIPT, NotifyCallback::STATUS_TEMPORARY_FAILURE, false],
            'permanent failure' => [NotifyCallback::TYPE_DELIVERY_RECEIPT, NotifyCallback::STATUS_PERMANENT_FAILURE, true],
            'technical failure' => [NotifyCallback::TYPE_DELIVERY_RECEIPT, NotifyCallback::STATUS_TECHNICAL_FAILURE, true],
            'returned letter' => [NotifyCallback::TYPE_RETURNED_LETTER, NotifyCallback::STATUS_RETURNED, true],
        ];
    }

    public function testFatalStatusesMatchIsFatal(): void
    {
        foreach (self::statuses() as [$type, $status, $fatal]) {
            self::assertSame($fatal, in_array($status, NotifyCallback::FATAL_STATUSES, true), $status);
        }
    }
}
