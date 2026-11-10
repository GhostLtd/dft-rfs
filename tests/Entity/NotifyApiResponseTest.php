<?php

namespace App\Tests\Entity;

use App\Entity\NotifyApiResponse;
use App\Entity\NotifyCallback;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Utility\AlphagovNotify\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NotifyApiResponseTest extends TestCase
{
    private function response(string $status, array $callbackStatuses = []): NotifyApiResponse
    {
        $response = (new NotifyApiResponse())->setData([AlphagovNotifyMessengerSubscriber::STATUS_KEY => $status]);
        foreach ($callbackStatuses as $callbackStatus) {
            $type = $callbackStatus === NotifyCallback::STATUS_RETURNED ? NotifyCallback::TYPE_RETURNED_LETTER : NotifyCallback::TYPE_DELIVERY_RECEIPT;
            (new NotifyCallback())->setType($type)->setStatus($callbackStatus)->setResponse($response);
        }
        return $response;
    }

    public static function problems(): array
    {
        return [
            'accepted, no callback yet' => [Reference::STATUS_ACCEPTED, [], false, false],
            'waiting for retry' => [Reference::STATUS_WAITING_FOR_RETRY, [], false, false],
            'send-time failure' => [Reference::STATUS_FAILED, [], true, false],
            'refused by our own SMS caps (says nothing about the number)' => [Reference::STATUS_REFUSED, [], false, false],
            'delivered' => [Reference::STATUS_ACCEPTED, [NotifyCallback::STATUS_DELIVERED], false, false],
            'temporary failure (not fatal)' => [Reference::STATUS_ACCEPTED, [NotifyCallback::STATUS_TEMPORARY_FAILURE], false, false],
            'permanent failure' => [Reference::STATUS_ACCEPTED, [NotifyCallback::STATUS_PERMANENT_FAILURE], false, true],
            'technical failure' => [Reference::STATUS_ACCEPTED, [NotifyCallback::STATUS_TECHNICAL_FAILURE], false, true],
            'returned letter' => [Reference::STATUS_ACCEPTED, [NotifyCallback::STATUS_RETURNED], false, true],
        ];
    }

    #[DataProvider('problems')]
    public function testDeliveryProblemKinds(string $status, array $callbacks, bool $sendTime, bool $afterSending): void
    {
        $response = $this->response($status, $callbacks);

        self::assertSame($sendTime, $response->isSendTimeFailure());
        self::assertSame($afterSending, $response->isAfterSendingFailure());
        self::assertSame($sendTime || $afterSending, $response->hasDeliveryProblem());
    }

    public function testProblemIsUnseenUntilMarkedSeen(): void
    {
        $response = $this->response(Reference::STATUS_ACCEPTED, [NotifyCallback::STATUS_RETURNED]);
        self::assertTrue($response->isDeliveryProblemUnseen());

        $response->setProblemSeenAt(new \DateTime());
        self::assertFalse($response->isDeliveryProblemUnseen());
    }

    public function testNoProblemIsNeverUnseen(): void
    {
        self::assertFalse($this->response(Reference::STATUS_ACCEPTED, [NotifyCallback::STATUS_DELIVERED])->isDeliveryProblemUnseen());
    }
}
