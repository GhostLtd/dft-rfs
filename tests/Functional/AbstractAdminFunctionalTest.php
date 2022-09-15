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

    protected function login(KernelBrowser $client): Crawler
    {
        $client->request('GET', "https://{$_ENV['ADMIN_HOSTNAME']}/login");

        return $client->submitForm('admin_login_login', [
            'admin_login[credentials][username]' => 'test',
            'admin_login[credentials][password]' => 'test'
        ]);
    }
}