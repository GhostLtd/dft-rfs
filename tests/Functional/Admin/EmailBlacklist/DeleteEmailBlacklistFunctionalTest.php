<?php

namespace App\Tests\Functional\Admin\EmailBlacklist;

use App\Entity\EmailBlacklist;
use App\Tests\DataFixtures\TestSpecific\EmailBlacklistRepositoryFixtures;
use App\Tests\Functional\Admin\AbstractAdminFunctionalTestCase;

class DeleteEmailBlacklistFunctionalTest extends AbstractAdminFunctionalTestCase
{
    public function testDeletePageRenders(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([EmailBlacklistRepositoryFixtures::class]);

        $emailBlacklist = $this->fixtureReferenceRepository->getReference('email-blacklist:blocked@example.com', EmailBlacklist::class);

        $browser->request('GET', "https://{$_ENV['ADMIN_HOSTNAME']}/email-blacklist/{$emailBlacklist->getId()}/delete");

        self::assertEquals(200, $browser->getResponse()->getStatusCode());
    }
}
