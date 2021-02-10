<?php


namespace App\EventSubscriber;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecureHeadersSubscriber implements EventSubscriberInterface
{
    public function kernelResponseEvent(ResponseEvent $event)
    {
        $event->getResponse()->headers->add([
            'X-frame-origin' => 'sameorigin',
            'X-Content-Type-Options' => 'nosniff',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self' 'unsafe-inline' *.getwisdom.io wss://producer.getwisdom.io;",
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'kernelResponseEvent',
        ];
    }
}