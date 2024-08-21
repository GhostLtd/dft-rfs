<?php

namespace App\Tests\NewFunctional\Wizard\RoRo;

use App\Tests\DataFixtures\RoRo\UserFixtures;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class LoginTest extends AbstractRoRoTest
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            UserFixtures::class,
        ]);

        parent::setUp();
    }

    public function testSuccessfulRoroLogin(): void
    {
        $this->client->request('GET', '/roro/logout');
        $this->client->request('GET', '/roro');

        $email = 'test@example.com';
        $this->formTestAction(
            '/roro/login',
            'ro_ro_login_sign_in',
            [
                new FormTestCase([], ["#ro_ro_login_username"]),
                new FormTestCase(['ro_ro_login[username]' => $email]),
            ],
        );

        $this->pathTestAction('/roro/check-email');

        $this->context
            ->outputHeader('Retrieve login link from message bus, and follow it')
            ->increaseActionIndex();

        $message = $this->fetchMessage();
        $personalisation = $message->getPersonalisation();

        $this->assertEquals($email, $message->getRecipient());
        $this->assertArrayHasKey('login_link', $personalisation);

        $loginLink = $personalisation['login_link'];
        $this->client->request('GET', $loginLink);

        $this->formTestAction(
            '/roro/authenticate',
            'login',
            [new FormTestCase([])]
        );

        $this->pathTestAction('#^/roro/operator/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $header = $this->client
            ->getCrawler()
            ->filter('h1')
            ->text();

        $this->assertStringContainsString('Dashboard', $header);
    }

    public function testNonExistentEmail(): void
    {
        $this->client->request('GET', '/roro');

        $email = 'non-existent@example.com';
        $this->formTestAction(
            '/roro/login',
            'ro_ro_login_sign_in',
            [
                new FormTestCase([], ["#ro_ro_login_username"]),
                new FormTestCase(['ro_ro_login[username]' => $email]),
            ],
        );

        $this->pathTestAction('/roro/check-email');

        $this->context
            ->outputHeader('Check that there is no message on the message bus')
            ->increaseActionIndex();

        $this->assertNull($this->fetchMessage(), 'Expected no message to be on the message bus');
    }
}