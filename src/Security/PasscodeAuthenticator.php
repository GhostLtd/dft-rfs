<?php

namespace App\Security;

use App\Entity\PasscodeUser;
use App\Form\PasscodeLoginType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
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

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, FormFactoryInterface $formFactory)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->formFactory = $formFactory;
    }

    public function supports(Request $request)
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
//        $credentials = [
//            'username' => $request->request->get('username'),
//            'password' => $request->request->get('password'),
//            'csrf_token' => $request->request->get('_csrf_token'),
//        ];
//        $request->getSession()->set(
//            Security::LAST_USERNAME,
//            $credentials['username']
//        );

//        return $credentials;

        return dump($request->request->get('passcode_login'));
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return PasscodeUser|object|UserInterface|null
     * @throws Exception
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate.passcode', $credentials['_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->entityManager->getRepository(PasscodeUser::class)->findOneBy(['username' => $credentials['passcode'][0]]);

        if (!$user) {
            // fake the password checking - so that attackers can't detect the difference
            $this->passwordEncoder->isPasswordValid(new PasscodeUser(), random_bytes(8));
            // fail authentication with a custom error
            throw new BadCredentialsException();
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['passcode'][1]);
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

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        /** @var PasscodeUser $user */
        $user = $token->getUser();
        if ($user->getDomesticSurvey()) {
            // redirect to domestic survey index
            return new RedirectResponse($this->urlGenerator->generate('domestic_survey_index'));
        }

        // For example : return new RedirectResponse($this->urlGenerator->generate('some_route'));
        throw new Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
