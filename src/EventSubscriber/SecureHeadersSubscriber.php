<?php

namespace App\EventSubscriber;

use App\Features;
use App\Utility\CspInlineScriptHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecureHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected string $appEnvironment,
        protected CspInlineScriptHelper $cspInlineScriptHelper,
        protected Features $features,
    ) {}

    public function kernelResponseEvent(ResponseEvent $event): void
    {
        $cspScriptSrc = "'self' {$this->nonce('js-detect')}";
        $cspStyleSrc = "'self' {$this->nonce('env-label')}";

        if ($this->appEnvironment === 'dev') {
            // This is the hash for the style block in data_collector/features.html.twig
            // Cannot use a nonce, since it's fetched by XHR (a separate request) and hence calculates a different value.
            $cspStyleSrc .= " 'sha256-BQJjz3ocAP+BIpDvmT26irrnyramDUQrNHkar7d4RjI='";
        }

        $event->getResponse()->headers->add([
            'X-frame-origin' => 'sameorigin',
            'X-Content-Type-Options' => 'nosniff',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' =>
                "default-src 'self'; ".
                "script-src $cspScriptSrc; ".
                "style-src $cspStyleSrc;",
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);

        if ($this->features->isEnabled(Features::SMARTLOOK_USER_SESSION_RECORDING))
        {
            $event->getResponse()->headers->add([
                'Content-Security-Policy' =>
                    "default-src 'self'; ".
                    "script-src $cspScriptSrc {$this->nonce('smartlook')} https://*.smartlook.com https://*.smartlook.cloud; ".
                    "style-src $cspStyleSrc; ".
                    "connect-src 'self' https://*.smartlook.com https://*.smartlook.cloud; ".
                    "child-src 'self' blob:; ".
                    "worker-src 'self' blob:",
            ]);
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'kernelResponseEvent',
        ];
    }

    protected function nonce(string $context): string
    {
        return "'nonce-{$this->cspInlineScriptHelper->getNonce($context)}'";
    }
}