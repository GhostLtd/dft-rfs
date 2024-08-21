<?php

namespace App\Security;

use App\Features;
use App\PreKernelFeatures;
use Exception;
use Google\Auth\AccessToken;
use Google\Cloud\Core\Compute\Metadata;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class GoogleIapAuthenticator extends AbstractAuthenticator
{
    protected bool $isGaeEnvironment;
    protected ManagementUserHelper $managementUserHelper;

    public function __construct(ManagementUserHelper $managementUserHelper)
    {
        $this->isGaeEnvironment = PreKernelFeatures::isEnabled(Features::GAE_ENVIRONMENT);
        $this->managementUserHelper = $managementUserHelper;
    }

    protected function getUserEmail(Request $request): string
    {
        $testUser = $request->server->get('TEST_GOOGLE_IAP_AUTH_USER');
        return $testUser ?? $request->headers->get('X-Goog-Authenticated-User-Email', 'no-email');
    }

    protected function getJwtAssertion(Request $request): ?string
    {
        if ($request->server->has('TEST_GOOGLE_IAP_AUTH_USER')) {
            return 'test';
        }

        return $request->headers->get('X-Goog-Iap-Jwt-Assertion', null);
    }

    protected function getUserId(Request $request): string
    {
        return $request->headers->get('X-Goog-Authenticated-User-Id', 'no-id');
    }

    public function supports(Request $request): ?bool
    {
        return
            $request->headers->has('X-Goog-Iap-Jwt-Assertion') ||
            (!$this->isGaeEnvironment && $request->server->has('TEST_GOOGLE_IAP_AUTH_USER'));
    }

    public function authenticate(Request $request): Passport
    {
        $email = $this->getUserEmail($request);

        $credentials = [
            'emailAddress' => $email,
            'userId' => $this->getUserId($request),
            'assertion' => $this->getJwtAssertion($request),
        ];

        $role = $this->managementUserHelper->isManagementDomain($email) ?
            'ROLE_ADMIN_IAP_MANAGER' :
            'ROLE_ADMIN_IAP_USER';

        return new Passport(
            new UserBadge('', fn() => new InMemoryUser(
                $email,
                $credentials['assertion'],
                [$role],
                true,
            )),
            new CustomCredentials(
                [$this, 'customAuthenticator'],
                $credentials
            ),
        );
    }

    public function customAuthenticator($credentials, UserInterface $user): bool
    {
        if (!$user instanceof InMemoryUser) {
            return false;
        }

        if ($user->getPassword() === false) {
            return false;
        }

        if (!$this->isGaeEnvironment) {
            return true;
        }

        try {
            $metadata = new Metadata();
            $audience = "/projects/{$metadata->getNumericProjectId()}/apps/{$metadata->getProjectId()}";
            $assertionId = $this->validateAssertion($user->getPassword(), $audience);
            return $assertionId === $credentials['userId'];
        } catch (Exception) {
            return false;
        }
    }

    private function validateAssertion(string $idToken, string $audience): string
    {
        $auth = new AccessToken();
        $info = $auth->verify($idToken, [
            'certsLocation' => AccessToken::IAP_CERT_URL,
            'throwException' => true,
        ]);

        if ($info === false) {
            throw new Exception('Google token verification failed');
        }

        if ($audience != $info['aud'] ?? '') {
            throw new Exception(sprintf(
                'Audience %s did not match expected %s', $info['aud'], $audience
            ));
        }

        if (empty($info['email']) || empty($info['sub'])) {
            throw new Exception('Google token verification does not contain email/sub.');
        }

        // The email address returned is the plain email address (without namespace)
        return $info['sub'];
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
