<?php

namespace App\Tests\Utility;

use App\Tests\Functional\AbstractFunctionalTest;
use App\Utility\RemoteActions;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RemoteActionsHmacTest extends AbstractFunctionalTest
{
    public const HMAC_ACTION = 'test-function';

    public function getAccessDeniedData(): array
    {
        return [
            ['wrong-action', 'now', $_ENV['APP_SECRET']],
            [self::HMAC_ACTION, '-31 seconds', $_ENV['APP_SECRET']],
            [self::HMAC_ACTION, '+31 seconds', $_ENV['APP_SECRET']],
            [self::HMAC_ACTION, 'now', "wrong-secret"],
        ];
    }

    /**
     * @dataProvider getAccessDeniedData
     */
    public function testHmacAccessDenied($testAction, $testTimeString, $testSecret)
    {
        $testTimestamp = date_create($testTimeString)->getTimestamp();
        $hmac = $this->generateHmac($testAction, $testTimestamp, $testSecret);

        try {
            RemoteActions::denyAccessUnlessHmacPasses($hmac, $testTimestamp, self::HMAC_ACTION);
        } catch (\Throwable $e) {
            self::assertSame(AccessDeniedHttpException::class, $e::class);
            return;
        }

        throw new \AssertionError("Access was not denied");
    }

    public function getAccessGrantedData(): array
    {
        return [
            [self::HMAC_ACTION, 'now', $_ENV['APP_SECRET']],
            [self::HMAC_ACTION, '+30 seconds', $_ENV['APP_SECRET']],
            [self::HMAC_ACTION, '-30 seconds', $_ENV['APP_SECRET']],
        ];
    }

    /**
     * @dataProvider getAccessGrantedData
     */
    public function testHmacAccessGranted($testAction, $testTimeString, $testSecret)
    {
        $testTimestamp = date_create($testTimeString)->getTimestamp();
        $hmac = $this->generateHmac($testAction, $testTimestamp, $testSecret);

        self::assertTrue(RemoteActions::denyAccessUnlessHmacPasses($hmac, $testTimestamp, self::HMAC_ACTION));
    }

    protected function generateHmac($action, $timestamp, $secret)
    {
        return hash_hmac('sha256', "{$action}:{$timestamp}", $secret);
    }
}
