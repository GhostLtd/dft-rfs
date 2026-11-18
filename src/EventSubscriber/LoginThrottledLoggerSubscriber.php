<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * Logs a login refused by login throttling (security.yaml), so that a brute-force attempt shows up in the logs. The
 * client's IP, not the username: that's the first half of a passcode user's credentials.
 */
class LoginThrottledLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [LoginFailureEvent::class => 'onLoginFailure'];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        if (!$event->getException() instanceof TooManyLoginAttemptsAuthenticationException) {
            return;
        }

        $this->logger->warning('Login throttled: too many attempts', [
            'ip' => $event->getRequest()->getClientIp(),
            'firewall' => $event->getFirewallName(),
        ]);
    }
}
