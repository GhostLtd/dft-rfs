<?php


namespace App\Tests\Controller\Cron;


use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;

class RequestConditionsTest extends WebTestCase
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