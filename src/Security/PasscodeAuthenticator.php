<?php

namespace App\Security;

use App\Controller\DomesticSurvey\IndexController as DomesticIndexController;
use App\Controller\InternationalSurvey\IndexController as InternationalIndexController;
use App\Controller\PreEnquiry\PreEnquiryController;
use App\Entity\PasscodeUser;
use App\Form\PasscodeLoginType;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class PasscodeAuthenticator extends AbstractLoginFormAuthenticator
{
    public const LOGIN_ROUTE = 'app_login';

    public function __construct(protected EntityManagerInterface $entityManager, protected FormFactoryInterface $formFactory, protected PasscodeGenerator $passcodeGenerator, protected UserPasswordHasherInterface $passwordHasher, protected UrlGeneratorInterface $urlGenerator)
    {
    }

    #[\Override]
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $form = $this->formFactory->create(PasscodeLoginType::class, []);
        $form->handleRequest($request);

        $data = $form->getData();
        $csrfTokenId = $form->getConfig()->getOption('csrf_token_id');

        $removeHashesDashesAndSpaces = fn($str) => str_replace(['#', '-', ' '], ['', '', ''], $str);

        $username = $removeHashesDashesAndSpaces($data['0'] ?? '');
        $password = $removeHashesDashesAndSpaces($data['1'] ?? '');

        return new Passport(
            new UserBadge($username),
            new CustomCredentials(function($credentials, UserInterface $user) {
                if (!$user instanceof PasscodeUser) {
                    return false;
                }

                // TODO: PasswordHasher looks to be a historical artifact since we now generate our passcodes,
                //       however, it is used in functional tests to verify the "test" password. Remove it.
                return
                    hash_equals($credentials, $this->passcodeGenerator->getPasswordForUser($user)) ||
                    $this->passwordHasher->isPasswordValid($user, $credentials);
            }, $password),
            [
                new CsrfTokenBadge($csrfTokenId, $data['token'] ?? ''),
            ]
        );
    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var PasscodeUser $user */
        $user = $token->getUser();
        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();

        $roles = $user->getRoles();
        return match (true) {
            in_array(PasscodeUser::ROLE_DOMESTIC_SURVEY_USER, $roles) => new RedirectResponse($this->urlGenerator->generate(DomesticIndexController::SUMMARY_ROUTE)),
            in_array(PasscodeUser::ROLE_INTERNATIONAL_SURVEY_USER, $roles) => new RedirectResponse($this->urlGenerator->generate(InternationalIndexController::SUMMARY_ROUTE)),
            in_array(PasscodeUser::ROLE_PRE_ENQUIRY_USER, $roles) => new RedirectResponse($this->urlGenerator->generate(PreEnquiryController::SUMMARY_ROUTE)),
            default => throw new AuthenticationException(),
        };
    }
}