<?php

namespace App\Security;

use App\Controller\DomesticSurvey\IndexController as DomesticIndexController;
use App\Controller\InternationalSurvey\IndexController as InternationalIndexController;
use App\Controller\PreEnquiry\PreEnquiryController;
use App\Entity\PasscodeUser;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class PasscodeAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private UserPasswordEncoderInterface $passwordEncoder;
    private FormFactoryInterface $formFactory;
    private PasscodeGenerator $passcodeGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder,
        FormFactoryInterface $formFactory,
        PasscodeGenerator $passcodeGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->formFactory = $formFactory;
        $this->passcodeGenerator = $passcodeGenerator;
    }

    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST')
            && $request->request->has('passcode_login');
    }

    public function getCredentials(Request $request)
    {
        return $request->request->get('passcode_login');
    }

    public function getUser($credentials, UserProviderInterface $userProvider): PasscodeUser
    {
        $token = new CsrfToken('authenticate.passcode', $credentials['_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->entityManager->getRepository(PasscodeUser::class)->findOneBy(['username' => $credentials['passcode'][0]]);

        if (!$user instanceof PasscodeUser) {
            try {
                // Fake the password checking - so that attackers can't detect the difference
                $this->passwordEncoder->isPasswordValid(new PasscodeUser(), random_bytes(8));
            } catch(\Exception $e) {}

            throw new BadCredentialsException();
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return is_array($credentials) &&
            isset($credentials['passcode'][1]) &&
            $this->checkPassword($user, $credentials['passcode'][1]);
    }

    protected function checkPassword(UserInterface $user, string $password): bool
    {
        if (!$user instanceof PasscodeUser) {
            return false;
        }

        return hash_equals($this->passcodeGenerator->getPasswordForUser($user), $password)
            || $this->passwordEncoder->isPasswordValid($user, $password);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     * @param $credentials
     * @return string|null
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['passcode'][1];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        /** @var PasscodeUser $user */
        $user = $token->getUser();
        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();

        switch (true)
        {
            case $user->hasRole(PasscodeUser::ROLE_DOMESTIC_SURVEY_USER):
                return new RedirectResponse($this->urlGenerator->generate(DomesticIndexController::SUMMARY_ROUTE));

            case $user->hasRole(PasscodeUser::ROLE_INTERNATIONAL_SURVEY_USER):
                return new RedirectResponse($this->urlGenerator->generate(InternationalIndexController::SUMMARY_ROUTE));

            case $user->hasRole(PasscodeUser::ROLE_PRE_ENQUIRY_USER):
                return new RedirectResponse($this->urlGenerator->generate(PreEnquiryController::SUMMARY_ROUTE));
        }

        throw new AuthenticationException();
    }

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
