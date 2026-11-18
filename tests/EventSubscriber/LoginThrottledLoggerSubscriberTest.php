<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\LoginThrottledLoggerSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * A throttled login is logged as a warning (so a brute-force attempt shows up in the logs), with the client's IP. Not
 * the username: it's the first half of the user's credentials.
 */
class LoginThrottledLoggerSubscriberTest extends TestCase
{
    public function testThrottledLoginIsLoggedAsAWarning(): void
    {
        $logger = new BufferingLogger();
        (new LoginThrottledLoggerSubscriber($logger))->onLoginFailure($this->event(new TooManyLoginAttemptsAuthenticationException(1)));

        $records = $logger->cleanLogs();
        self::assertCount(1, $records);
        [$level, $message, $context] = $records[0];
        self::assertSame('warning', $level);
        self::assertStringContainsString('throttled', $message);
        self::assertSame(['ip' => '203.0.113.7', 'firewall' => 'main'], $context);
    }

    public function testOtherLoginFailuresAreNotLogged(): void
    {
        $logger = new BufferingLogger();
        (new LoginThrottledLoggerSubscriber($logger))->onLoginFailure($this->event(new BadCredentialsException()));

        self::assertSame([], $logger->cleanLogs());
    }

    private function event(\Symfony\Component\Security\Core\Exception\AuthenticationException $exception): LoginFailureEvent
    {
        $request = Request::create('/login', 'POST', server: ['REMOTE_ADDR' => '203.0.113.7']);
        return new LoginFailureEvent($exception, $this->createStub(AuthenticatorInterface::class), $request, null, 'main');
    }
}
