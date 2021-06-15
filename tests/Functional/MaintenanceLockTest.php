<?php

namespace App\Tests\Functional;

use App\Tests\DataFixtures\MaintenanceLockFixtures;
use App\Tests\DataFixtures\MaintenanceLockWithWhitelistFixtures;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

class MaintenanceLockTest extends AbstractFrontendFunctionalTest
{
    public function testMaintenanceLockInactive()
    {
        // we have to load no fixtures to get an empty database
        $this->loadFixtures([]);
        $this->verifyAccessNormal($this->browser);
    }

    public function testMaintenanceLockActive()
    {
        $this->loadFixtures([MaintenanceLockFixtures::class]);

        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/");
        self::assertStringContainsString('To complete the survey', $response->outerHtml());

        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/login");
        self::assertStringContainsString('Scheduled maintenance - Service unavailable', $response->outerHtml());
    }

    public function testMaintenanceLockActiveWhitelist()
    {
        $this->loadFixtures([MaintenanceLockWithWhitelistFixtures::class]);
        $this->verifyAccessNormal($this->browser);
    }

    protected function verifyAccessNormal(KernelBrowser $browser)
    {
        $response = $browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/");
        self::assertStringContainsString('To complete the survey', $response->outerHtml());

        $response = $browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/login");
        self::assertStringContainsString('Enter your survey access codes', $response->outerHtml());
    }
}