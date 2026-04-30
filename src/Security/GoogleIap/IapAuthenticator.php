<?php

namespace App\Security\GoogleIap;

use Ghost\GovUkCoreBundle\Security\GoogleIap\IapAuthenticator as BundleIapAuthenticator;
use Symfony\Component\HttpFoundation\Request;

class IapAuthenticator extends BundleIapAuthenticator
{
    #[\Override]
    public function supports(Request $request): ?bool
    {
        if (parent::supports($request)) {
            return true;
        }
        // Allow non-GAE environments to fake-login via the env var (used by Panther tests).
        return !$this->isGaeEnvironment && $request->server->has('TEST_GOOGLE_IAP_AUTH_USER');
    }

    #[\Override]
    protected function getUserEmail(Request $request): string
    {
        return $request->server->get('TEST_GOOGLE_IAP_AUTH_USER')
            ?? parent::getUserEmail($request);
    }

    #[\Override]
    protected function getJwtAssertion(Request $request): ?string
    {
        if ($request->server->has('TEST_GOOGLE_IAP_AUTH_USER')) {
            return 'test';
        }
        return parent::getJwtAssertion($request);
    }
}
