<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractFrontendFunctionalTest extends AbstractFunctionalTest
{
    protected function login(KernelBrowser $client): Crawler
    {
        $client->request('GET', "https://{$_ENV['FRONTEND_HOSTNAME']}/login");

        return $client->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][0]' => 'test',
            'passcode_login[passcode][1]' => 'test'
        ]);
    }
}