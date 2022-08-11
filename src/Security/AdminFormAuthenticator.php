<?php

namespace App\Security;

use App\Form\AdminLoginType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class AdminFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public const LOGIN_ROUTE = 'admin_login';

    protected FormFactoryInterface $formFactory;
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(FormFactoryInterface $formFactory, UrlGeneratorInterface $urlGenerator)
    {
        $this->formFactory = $formFactory;
        $this->urlGenerator = $urlGenerator;
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    public function authenticate(Request $request): Passport
    {
        $form = $this->formFactory->create(AdminLoginType::class, []);
        $form->handleRequest($request);

        $data = $form->getData();
        $csrfTokenId = $form->getConfig()->getOption('csrf_token_id');

        return new Passport(
            new UserBadge($data['username'] ?? ''),
            new PasswordCredentials($data['password'] ?? ''),
            [
                new CsrfTokenBadge($csrfTokenId, $data['token'] ?? ''),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
// TODO: Reinstate this mechanism?
//        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
//            return new RedirectResponse($targetPath);
//        }

        return new RedirectResponse($this->urlGenerator->generate('admin_index'));
    }
}