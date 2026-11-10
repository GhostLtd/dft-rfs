<?php

namespace App\Tests\Controller;

use App\Controller\NotifyCallbackController;
use App\Utility\AlphagovNotify\Callback\CallbackOutcome;
use App\Utility\AlphagovNotify\Callback\CallbackProcessor;
use App\Utility\AlphagovNotify\Callback\InvalidCallbackException;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Notify never sends a 4xx'd callback again, so each one must leave a trace (a token typo would otherwise drop every
 * callback silently), and anything else must not be turned into a 4xx.
 */
class NotifyCallbackControllerTest extends TestCase
{
    /** @var list<array{string, string, array}> */
    public array $logs = [];

    private function controller(CallbackProcessor $processor): NotifyCallbackController
    {
        $logger = new class($this) extends AbstractLogger {
            public function __construct(private readonly NotifyCallbackControllerTest $test) {}

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->test->logs[] = [(string) $level, (string) $message, $context];
            }
        };

        return new NotifyCallbackController($processor, $logger, 'the-token');
    }

    private function request(string $body, ?string $token = 'the-token'): Request
    {
        $request = Request::create('/notify/callback/delivery-receipt', 'POST', content: $body);
        if ($token !== null) {
            $request->headers->set('Authorization', "Bearer {$token}");
        }
        return $request;
    }

    public function testUnauthorisedIsLoggedAsAWarning(): void
    {
        $response = $this->controller($this->createStub(CallbackProcessor::class))->callback($this->request('{}', 'wrong'), 'delivery-receipt');

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['warning'], array_column($this->logs, 0));
        self::assertSame('delivery-receipt', $this->logs[0][2]['type']);
    }

    public function testInvalidPayloadIsLoggedAsAWarningWithoutTheBody(): void
    {
        $processor = $this->createStub(CallbackProcessor::class);
        $processor->method('process')->willThrowException(new InvalidCallbackException('Missing or unexpected status'));

        $response = $this->controller($processor)->callback($this->request('{"to":"someone@example.com"}'), 'delivery-receipt');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['warning'], array_column($this->logs, 0));
        self::assertSame('Missing or unexpected status', $this->logs[0][2]['reason']);
        self::assertStringNotContainsString('someone@example.com', json_encode($this->logs));
    }

    public function testOtherFailuresAreNotTurnedIntoA4xx(): void
    {
        $processor = $this->createStub(CallbackProcessor::class);
        $processor->method('process')->willThrowException(new \RuntimeException('Database gone'));

        $this->expectException(\RuntimeException::class);
        $this->controller($processor)->callback($this->request('{}'), 'delivery-receipt');
    }

    public function testStoredCallbackIsNoContent(): void
    {
        $processor = $this->createStub(CallbackProcessor::class);
        $processor->method('process')->willReturn(CallbackOutcome::STORED);

        self::assertSame(204, $this->controller($processor)->callback($this->request('{}'), 'delivery-receipt')->getStatusCode());
        self::assertSame([], $this->logs);
    }
}
