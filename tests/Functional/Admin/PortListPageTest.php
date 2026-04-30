<?php

namespace App\Tests\Functional\Admin;

use App\Tests\DataFixtures\RoRo\RouteAndPortFixtures;

/**
 * Smoke test for the ListPage framework after migration to ghost/govuk-core-bundle.
 * Exercises the most-used surface: rendering, default ordering, sortable column header,
 * filtering form submission. PortListPage is small enough to be a clean canary.
 */
class PortListPageTest extends AbstractAdminFunctionalTest
{
    private const string UK_PORTS_URL = '/ports/uk';

    public function testListRendersWithSeededPorts(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([RouteAndPortFixtures::class]);

        $browser->request('GET', "https://{$_ENV['ADMIN_HOSTNAME']}".self::UK_PORTS_URL);

        self::assertEquals(200, $browser->getResponse()->getStatusCode());
        $body = $browser->getResponse()->getContent();
        self::assertStringContainsString('Portfoot', $body);
        self::assertStringContainsString('Southester', $body);
    }

    public function testDefaultOrderIsByNameAscending(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([RouteAndPortFixtures::class]);
        $browser->request('GET', "https://{$_ENV['ADMIN_HOSTNAME']}".self::UK_PORTS_URL);

        $body = $browser->getResponse()->getContent();
        $portfootPos = strpos($body, 'Portfoot');
        $southesterPos = strpos($body, 'Southester');

        self::assertNotFalse($portfootPos);
        self::assertNotFalse($southesterPos);
        self::assertLessThan(
            $southesterPos,
            $portfootPos,
            'Default order is by Name ASC, so Portfoot should appear before Southester',
        );
    }

    public function testReversedOrderingViaQueryString(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([RouteAndPortFixtures::class]);
        $browser->request('GET', "https://{$_ENV['ADMIN_HOSTNAME']}".self::UK_PORTS_URL.'?orderBy=name&orderDirection=DESC');

        self::assertEquals(200, $browser->getResponse()->getStatusCode());
        $body = $browser->getResponse()->getContent();
        $portfootPos = strpos($body, 'Portfoot');
        $southesterPos = strpos($body, 'Southester');

        self::assertNotFalse($portfootPos);
        self::assertNotFalse($southesterPos);
        self::assertGreaterThan(
            $southesterPos,
            $portfootPos,
            'orderBy=name&orderDirection=DESC should put Southester before Portfoot',
        );
    }

    public function testFilteringByName(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([RouteAndPortFixtures::class]);

        // ListPageForm uses an empty block prefix, so filter fields go into the
        // top-level query string. Field id 'name' is derived from label 'Name'.
        // The 'submit=' marker tells Symfony the GET form was submitted.
        $browser->request('GET', "https://{$_ENV['ADMIN_HOSTNAME']}".self::UK_PORTS_URL.'?name=Portfoot&submit=');

        self::assertEquals(200, $browser->getResponse()->getStatusCode());
        $body = $browser->getResponse()->getContent();
        self::assertStringContainsString('Portfoot', $body);
        self::assertStringNotContainsString('Southester', $body);
    }

    public function testInvalidPageNumberReturns404(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([RouteAndPortFixtures::class]);
        $browser->request('GET', "https://{$_ENV['ADMIN_HOSTNAME']}".self::UK_PORTS_URL.'?page=99');

        self::assertEquals(404, $browser->getResponse()->getStatusCode());
    }
}
