<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoroAuthenticationHelper implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface, AuthenticationSuccessHandlerInterface
{
    protected TranslatorInterface $translator;
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator)
    {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_roro_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $message = $exception->getPrevious()?->getMessage();

        if (!$message) {
            $message = $exception->getMessage();
        }

        if ($message === 'User not found.') {
            // Ultimately, these messages are generated in:
            // * LoginLinkHandler -> consumeLoginLink()
            // * SignatureHasher  -> verifySignatureHash()
            //
            // We want to give some more detail (e.g. whether it's invalid or whether its expired), but don't want to
            // info that could potentially be a security information leak (e.g. User not found).
            $message = 'Invalid or expired login link.';
        }

        if ($message === 'Login link can only be used "1" times.') {
            $message = 'Login link can only be used once.';
        }

        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $message);
        return new RedirectResponse($this->urlGenerator->generate('app_roro_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_roro_login'));
    }
}