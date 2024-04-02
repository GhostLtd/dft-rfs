<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractAdminFunctionalTest extends AbstractFunctionalTest
{
    protected function getBrowserLoadFixturesAndLogin(array $fixtures): KernelBrowser
    {
        $this->loadFixtures($fixtures);
        $this->login($this->browser);

        return $this->browser;
    }

    protected function getServerParameters(): array
    {
        return [
            'HTTP_X-Goog-Iap-Jwt-Assertion' => 'foo',
            'HTTP_X-Goog-Authenticated-User-Email' => 'test@example.com',
        ];
    }

    protected function login(KernelBrowser $client): Crawler
    {
        return $client->request('GET', "https://{$_ENV['ADMIN_HOSTNAME']}/");
    }
}
