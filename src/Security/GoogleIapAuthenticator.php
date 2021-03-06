<?php

namespace App\Security;

use Google\Auth\AccessToken;
use Google\Cloud\Core\Compute\Metadata;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class GoogleIapAuthenticator extends AbstractGuardAuthenticator
{
    const EXTRA_FIELD_USER_ID = 'IAPid';
    private $appEnvironment;
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    public function __construct($appEnvironment, UrlGeneratorInterface $urlGenerator)
    {
        $this->appEnvironment = $appEnvironment;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request)
    {
        return $request->headers->has('X-Goog-Iap-Jwt-Assertion');
    }

    public function getCredentials(Request $request)
    {
        return [
            'emailAddress' => $request->headers->get('X-Goog-Authenticated-User-Email', 'no-email'),
            'userId' => $request->headers->get('X-Goog-Authenticated-User-Id', 'no-id'),
            'assertion' => $request->headers->get('X-Goog-Iap-Jwt-Assertion', false),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return new User(
            $credentials['emailAddress'],
            $credentials['assertion'],
            ['ROLE_ADMIN_IAP_USER'],
            true, true, true, true,
            [self::EXTRA_FIELD_USER_ID => $credentials['userId']]
        );
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if ($user->getPassword() === false) {
            return false;
        }

        /** @var User $user */
        try {
            $metadata = new Metadata();
            $audience = sprintf(
                '/projects/%s/apps/%s',
                $metadata->getNumericProjectId(),
                $metadata->getProjectId()
            );
            list($assertionEmailAddress, $assertionId) = $this->validateAssertion($user->getPassword(), $audience);
            return $assertionId === $user->getExtraFields()[self::EXTRA_FIELD_USER_ID];
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validateAssertion(string $idToken, string $audience) : array
    {
        $auth = new AccessToken();
        $info = $auth->verify($idToken, [
            'certsLocation' => AccessToken::IAP_CERT_URL,
            'throwException' => true,
        ]);

        if ($info === false) {
            throw new \Exception('Google token verification failed');
        }

        if ($audience != $info['aud'] ?? '') {
            throw new \Exception(sprintf(
                'Audience %s did not match expected %s', $info['aud'], $audience
            ));
        }

        if (empty($info['email']) || empty($info['sub'])) {
            throw new \Exception('Google token verification does not contain email/sub.');
        }

        // The email address returned is the plain email address (without namespace)
        return [$info['email'], $info['sub']];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($request->attributes->get('_route') === 'admin_index') {
            $message = $authException->getMessage() ?? 'Token missing or invalid';
            throw new UnauthorizedHttpException('OAuth', $message);
        }
        return new RedirectResponse($this->urlGenerator->generate('admin_index'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // doing nothing allows fallback to other auth methods
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
