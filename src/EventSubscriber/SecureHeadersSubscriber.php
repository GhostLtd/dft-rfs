<?php

namespace App\EventSubscriber;

use App\Features;
use App\Twig\SmartlookExtension;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecureHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(protected SmartlookExtension $smartlookExtension)
    {}

    // NOTES:
    // - If you dump() anywhere, then the CSP header gets removed by WebDebugToolbarListener.php (at ~line 99)!
    // - Mentions of unsafe-inline in dev mode probably originate from ContentSecurityPolicyHandler.php (again
    //   part of the web-profiler-bundle)
    public function kernelResponseEvent(ResponseEvent $event): void
    {
        $headers = $event->getResponse()->headers;
        $cspHeader = $headers->get('Content-Security-Policy');

        if ($cspHeader === null) {
            // If there's no CSP header, no point trying to add things to it!
            // N.B. this happens on error pages, for example
            return;
        }

        $cspRules = array_map(trim(...), explode(';', $cspHeader));

        $isSmartlookEnabled = $this->smartlookExtension->getSmartLookApiKey() !== null;

        // If we want the CSP rules to comprise only authorised sources, we need to set
        // e.g. style-src: 'none'

        // However, if we then use csp_nonce('style') to add nonces, nelmio generates
        // a header that looks like so:
        // style-src: 'none' 'nonce-abc123'

        // Browsers moan about this, and so here we strip the 'none' if there are other
        // entries

        // (So if csp_nonce() not called, we end up with style-src: 'none', and if there
        //  are calls, we end up with style-src: 'nonce-abc123' 'nonce-def234')
        foreach($cspRules as $idx => $cspRule) {
            $cspRuleParts = explode(' ', $cspRule);

            $cspRuleName = $cspRuleParts[0] ?? null;
            $cspRuleEntries = array_slice($cspRuleParts, 1);

            // N.B. Not exhaustive - amend as needed
            if (in_array($cspRuleName, [
                'child-src',
                'connect-src',
                'font-src',
                'img-src',
                'media-src',
                'script-src',
                'style-src',
            ])) {
                if ($isSmartlookEnabled) {
                    // Add a couple of hostnames if smartlook enabled...
                    if (in_array($cspRuleName, ['connect-src', 'script-src'])) {
                        $cspRuleEntries[] = 'https://*.smartlook.com';
                        $cspRuleEntries[] = 'https://*.smartlook.cloud';
                    }
                }

                $hasMultipleEntries = count($cspRuleEntries) > 1;
                $cspRuleEntries = array_filter($cspRuleEntries, fn($entry) =>
                    !$hasMultipleEntries ||
                    !in_array(strtolower($entry), ["'none'", "none", '"none"'])
                );

                $cspRules[$idx] = join(' ', array_merge([$cspRuleName], $cspRuleEntries));
            }
        }

        $headers->set('Content-Security-Policy', join('; ', $cspRules));
    }


    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['kernelResponseEvent', -128],
        ];
    }
}