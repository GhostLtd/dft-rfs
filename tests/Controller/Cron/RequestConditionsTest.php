<?php

namespace App\Tests\Controller\Cron;

use App\Tests\Functional\AbstractFrontendFunctionalTest;

class RequestConditionsTest extends AbstractFrontendFunctionalTest
{
    public function cronUrlsProvider(): array
    {
        return [
            ['/cron/test'],
        ];
    }

    /**
     * @dataProvider cronUrlsProvider
     */
    public function testConditionsNotMet($url): void
    {
        $this->loadFixtures([]);

        $this->browser->request('GET', "https://rfs-frontend.localhost{$url}");
        self::assertResponseStatusCodeSame(404);

        $this->browser->request('GET', "https://rfs-frontend.localhost{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'false',
        ]);
        self::assertResponseStatusCodeSame(404);

        $this->browser->request('GET', "https://rfs-frontend.localhost{$url}", [], [], [
            'HTTP_X_Cloudscheduler' => 'false',
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @dataProvider cronUrlsProvider
     */
    public function testConditionsMet($url): void
    {
        $this->loadFixtures([]);

        $this->browser->request('GET', "https://rfs-frontend.localhost{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);

        $this->browser->request('GET', "https://rfs-frontend.localhost{$url}", [], [], [
            'HTTP_X_Cloudscheduler' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);

        $this->browser->request('GET', "https://rfs-frontend.localhost{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'true',
            'HTTP_X_Cloudscheduler' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);
    }
}