<?php

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeoutTest extends AbstractFrontendFunctionalTest
{
    public function testRefreshSessionEndpointReturnsTimeoutTimes(): void
    {
        $this->loadFixtures([]);

        $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/core/refresh-session");
        $response = $this->browser->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->headers->get('Content-Type'));

        $body = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('warning', $body);
        self::assertArrayHasKey('expiry', $body);

        $warning = new \DateTimeImmutable($body['warning']);
        $expiry = new \DateTimeImmutable($body['expiry']);
        self::assertLessThan($expiry, $warning, 'Warning time must precede expiry time');

        $now = new \DateTimeImmutable();
        self::assertGreaterThan($now, $warning, 'Warning time should be in the future');
        self::assertGreaterThan($now, $expiry, 'Expiry time should be in the future');
    }

    public function testLegacyRefreshSessionRouteIsGone(): void
    {
        $this->loadFixtures([]);

        $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/refresh-session");
        $response = $this->browser->getResponse();

        self::assertSame(
            Response::HTTP_NOT_FOUND,
            $response->getStatusCode(),
            'The legacy /refresh-session route should be removed in favour of /core/refresh-session',
        );
    }
}
