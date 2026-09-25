<?php

namespace App\Tests\Messenger;

use App\Messenger\SmsAllowanceMiddleware;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\TraceableMessageBus;

/**
 * Wiring only, no dispatch: SmsAllowanceMiddleware charges/refuses an SMS attempt before it reaches
 * send_message, so a refusal never gets as far as the transport. Resolves the real container bus rather
 * than "app.test.message_bus" (which other kernel tests override), so this test class must not itself
 * need that override.
 */
class SmsAllowanceMiddlewareWiringTest extends KernelTestCase
{
    public function testSmsAllowanceMiddlewareRunsBeforeSendMessage(): void
    {
        $bus = static::getContainer()->get('messenger.bus.default');

        // In debug (profiler-enabled) environments the bus is wrapped in a TraceableMessageBus; unwrap to
        // the real Symfony\Component\Messenger\MessageBus that actually holds the middleware chain.
        while ($bus instanceof TraceableMessageBus) {
            $bus = (new \ReflectionProperty(TraceableMessageBus::class, 'decoratedBus'))->getValue($bus);
        }

        self::assertInstanceOf(MessageBus::class, $bus);

        $middlewareAggregate = (new \ReflectionProperty(MessageBus::class, 'middlewareAggregate'))->getValue($bus);
        $middleware = iterator_to_array($middlewareAggregate->getIterator(), false);

        $smsAllowanceIndex = null;
        $sendMessageIndex = null;
        foreach ($middleware as $index => $item) {
            if ($item instanceof SmsAllowanceMiddleware) {
                $smsAllowanceIndex = $index;
            }
            if ($item instanceof SendMessageMiddleware) {
                $sendMessageIndex = $index;
            }
        }

        self::assertNotNull($smsAllowanceIndex, 'SmsAllowanceMiddleware is not registered on messenger.bus.default');
        self::assertNotNull($sendMessageIndex, 'SendMessageMiddleware is not registered on messenger.bus.default');
        self::assertLessThan($sendMessageIndex, $smsAllowanceIndex, 'SmsAllowanceMiddleware must run before send_message');
    }
}
