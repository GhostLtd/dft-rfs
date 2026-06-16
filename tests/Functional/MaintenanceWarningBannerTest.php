<?php

namespace App\Tests\Functional;

use App\Tests\DataFixtures\MaintenanceWarningFixtures;
use Symfony\Component\HttpFoundation\Request;

class MaintenanceWarningBannerTest extends AbstractFrontendFunctionalTestCase
{
    public function testBannerShownWhenWarningActive(): void
    {
        $this->loadFixtures([MaintenanceWarningFixtures::class]);

        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/login");
        self::assertStringContainsString('Scheduled maintenance', $response->outerHtml());
        self::assertStringContainsString('govuk-notification-banner', $response->outerHtml());
    }

    public function testBannerHiddenWhenNoWarning(): void
    {
        $this->loadFixtures([]);

        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/login");
        self::assertStringNotContainsString('Scheduled maintenance', $response->outerHtml());
    }

    /**
     * The banner is scoped per-action via #[MaintenanceWarningBanner], so a non-attributed
     * action on a controller that has a banner action elsewhere must NOT show it.
     */
    public function testBannerNotShownOnNonAttributedActionOfSameController(): void
    {
        $this->loadFixtures([MaintenanceWarningFixtures::class]);

        $response = $this->browser->request(Request::METHOD_GET, "https://{$_ENV['FRONTEND_HOSTNAME']}/accessibility-statement");
        self::assertStringNotContainsString('Scheduled maintenance', $response->outerHtml());
    }
}
