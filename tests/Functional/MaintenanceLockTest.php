<?php

namespace App\Tests\Functional;

use App\Tests\DataFixtures\MaintenanceLockFixtures;
use App\Tests\DataFixtures\MaintenanceLockWithWhitelistFixtures;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class MaintenanceLockTest extends AbstractFrontendFunctionalTestCase
{
    public function testMaintenanceLockInactive()
    {
        // we have to load no fixtures to get an empty database
        $this->loadFixtures([]);
        $this->verifyAccessNormal();
    }

    public function testMaintenanceLockActive()
    {
        $this->loadFixtures([MaintenanceLockFixtures::class]);

        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/");
        self::assertStringContainsString('To complete the survey', $response->outerHtml());

        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/login");
        self::assertStringContainsString('Scheduled maintenance - Service unavailable', $response->outerHtml());
    }

    public function testMaintenanceLockActiveUnknownUrlIsNotFound()
    {
        $this->loadFixtures([MaintenanceLockFixtures::class]);

        // The maintenance lock only gates the main request, so it no longer takes over the 404 page
        // (rendered in a sub-request) for a URL that doesn't exist
        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/no-such-page");
        self::assertResponseStatusCodeSame(404);
        self::assertStringNotContainsString('Scheduled maintenance', $response->outerHtml());
    }

    public function testMaintenanceLockActiveWhitelist()
    {
        $this->loadFixtures([MaintenanceLockWithWhitelistFixtures::class]);
        $this->verifyAccessNormal();
    }

    protected function verifyAccessNormal()
    {
        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/");
        self::assertStringContainsString('To complete the survey', $response->outerHtml());

        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/login");
        self::assertStringContainsString('Enter your survey access codes', $response->outerHtml());
    }
}