<?php

namespace App\Security;

use Google\Auth\AccessToken;
use Google\Cloud\Core\Compute\Metadata;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class GoogleIapAuthenticator extends AbstractGuardAuthenticator
{
    public function supports(Request $request)
    {
        return $request->headers->has('X-Goog-Iap-Jwt-Assertion');
    }

    public function getCredentials(Request $request)
    {
        return [
            'username' => $request->headers->get('X-Goog-Authenticated-User-Id', 'no-username'),
            'assertion' => $request->headers->get('X-Goog-Iap-Jwt-Assertion'),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return new User($credentials['username'], $credentials['assertion'], ['ROLE_ADMIN_USER']);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        try {
            $metadata = new Metadata();
            $audience = sprintf(
                '/projects/%s/apps/%s',
                $metadata->getNumericProjectId(),
                $metadata->getProjectId()
            );
            list($email, $id) = $this->validateAssertion($user->getPassword(), $audience);
            return $email === $user->getUsername();
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

        if ($audience != $info['aud'] ?? '') {
            throw new \Exception(sprintf(
                'Audience %s did not match expected %s', $info['aud'], $audience
            ));
        }

        return [$info['email'], $info['sub']];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw new \LogicException("expected IAP headers");
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
