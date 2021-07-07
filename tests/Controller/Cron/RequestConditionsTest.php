<?php


namespace App\Tests\Controller\Cron;


use App\Tests\Functional\AbstractFrontendFunctionalTest;

class RequestConditionsTest extends AbstractFrontendFunctionalTest
{
    public function cronUrlsProvider()
    {
        return [
            ['/cron/test'],
        ];
    }

    /**
     * @dataProvider cronUrlsProvider
     */
    public function testConditionsNotMet($url)
    {
        $this->loadFixtures([]);

        $client = static::createClient();
        $client->request('GET', "https://frontend.rfs.test{$url}");
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', "https://frontend.rfs.test{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'false',
        ]);
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', "https://frontend.rfs.test{$url}", [], [], [
            'HTTP_X_Cloudscheduler' => 'false',
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @dataProvider cronUrlsProvider
     */
    public function testConditionsMet($url)
    {
        $this->loadFixtures([]);

        $client = static::createClient();
        $client->request('GET', "https://frontend.rfs.test{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);

        $client->request('GET', "https://frontend.rfs.test{$url}", [], [], [
            'HTTP_X_Cloudscheduler' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);

        $client->request('GET', "https://frontend.rfs.test{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'true',
            'HTTP_X_Cloudscheduler' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);
    }

}