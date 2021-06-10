<?php

namespace App\Tests\Functional\Deployment;

use App\Tests\DataFixtures\MaintenanceLockFixtures;
use App\Tests\DataFixtures\MaintenanceLockWithWhitelistFixtures;
use App\Tests\Functional\AbstractFrontendFunctionalTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

class MaintenanceLockTest extends AbstractFrontendFunctionalTest
{
    public function testMaintenanceLockInactive()
    {
        // we have to load no fixtures to get an empty database
        $browser = $this->getBrowserAndLoadFixtures([]);
        $this->verifyAccessNormal($browser);
    }

    public function testMaintenanceLockActive()
    {
        $browser = $this->getBrowserAndLoadFixtures([MaintenanceLockFixtures::class]);

        $response = $browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/");
        self::assertStringContainsString('To complete the survey', $response->outerHtml());

        $response = $browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/login");
        self::assertStringContainsString('Scheduled maintenance - Service unavailable', $response->outerHtml());
    }

    public function testMaintenanceLockActiveWhitelist()
    {
        $browser = $this->getBrowserAndLoadFixtures([MaintenanceLockWithWhitelistFixtures::class]);
        $this->verifyAccessNormal($browser);
    }

    protected function verifyAccessNormal(KernelBrowser $browser)
    {
        $response = $browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/");
        self::assertStringContainsString('To complete the survey', $response->outerHtml());

        $response = $browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/login");
        self::assertStringContainsString('Enter your survey access codes', $response->outerHtml());
    }
}