<?php

namespace App\Tests\Functional;

use App\Entity\PasscodeUser;
use App\Utility\PasscodeGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractFrontendFunctionalTestCase extends AbstractFunctionalTestCase
{
    private PasscodeGenerator $passcodeGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passcodeGenerator = $this->getContainer()->get(PasscodeGenerator::class);
    }

    protected function login(KernelBrowser $client): Crawler
    {
        $client->request('GET', "https://{$_ENV['FRONTEND_HOSTNAME']}/login");

        $user = $this->fixtureReferenceRepository->getReference('user:frontend', PasscodeUser::class);
        return $client->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][0]' => $user->getUsername(),
            'passcode_login[passcode][1]' => $this->passcodeGenerator->getPasswordForUser($user),
        ]);
    }
}