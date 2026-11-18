<?php

namespace App\Tests\Controller\Cron;

use App\Tests\Functional\AbstractFrontendFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RequestConditionsTest extends AbstractFrontendFunctionalTestCase
{
    public static function cronUrlsProvider(): array
    {
        return [
            ['/cron/test'],
        ];
    }

    #[DataProvider('cronUrlsProvider')]
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

        // Not trusted: unlike X-Appengine-Cron, App Engine doesn't strip it from outside requests, so anyone could
        // send it
        $this->browser->request('GET', "https://rfs-frontend.localhost{$url}", [], [], [
            'HTTP_X_Cloudscheduler' => 'true',
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    #[DataProvider('cronUrlsProvider')]
    public function testConditionsMet($url): void
    {
        $this->loadFixtures([]);

        $this->browser->request('GET', "https://rfs-frontend.localhost{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);

        $this->browser->request('GET', "https://rfs-frontend.localhost{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'true',
            'HTTP_X_Cloudscheduler' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);
    }
}