<?php

namespace App\Tests\NewFunctional\Wizard\Admin;

/**
 * A page number so large that its offset overflows (?page= is capped at PHP_INT_MAX) is a 404, not a 500. Found by
 * DAST (local/2026-11-17-dast-bug-log.md, B2); fixed in ghost/govuk-core-bundle 6.2.1 (AbstractListPage::getData()).
 */
class AdminListPaginationTest extends AbstractAdminTestCase
{
    public function testHugePageNumberIsNotFound(): void
    {
        $this->initialiseTest([]);

        $this->client->request('GET', '/csrgt/surveys-gb/?page=99999999999999999999');

        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    /** Control: the list itself works */
    public function testFirstPageWorks(): void
    {
        $this->initialiseTest([]);

        $this->client->request('GET', '/csrgt/surveys-gb/?page=1');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
