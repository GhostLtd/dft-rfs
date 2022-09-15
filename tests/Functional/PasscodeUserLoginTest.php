<?php


namespace App\Tests\Functional;


use App\Entity\PasscodeUser;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use App\Tests\DataFixtures\PasscodeLoginFixtures;
use App\Tests\DataFixtures\UserFixtures;
use App\Utility\PasscodeGenerator;

class PasscodeUserLoginTest extends AbstractFunctionalTest
{
    private PasscodeGenerator $passcodeGenerator;

    protected function setUp()
    {
        parent::setUp();
        $this->browser->followRedirects(true);

        $container = self::$kernel->getContainer()->get('test.service_container');
        $this->passcodeGenerator = $container->get(PasscodeGenerator::class);
    }

    public function dataProvider()
    {
        return [
            ['user:frontend', 'test', true],
            ['user:frontend', null, true],
            ['user:frontend', 'password', false],
            ['user:frontend:null-password', null, true],
            ['user:frontend:null-password', 'password', false],
            ['other', 'test', false],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testLogin($usernameOrRef, $password, $expectedValidLogin)
    {
        $this->loadFixtures([PasscodeLoginFixtures::class]);

        if ($this->fixtureReferenceRepository->hasReference($usernameOrRef)) {
            /** @var PasscodeUser $user */
            $user = $this->fixtureReferenceRepository->getReference($usernameOrRef);
            $username = $user->getUsername();
        } else {
            $username = $usernameOrRef;
            $user = null;
        }

        // If dataProvider password is null, use regenerated one
        if ($password === null) {
            self::assertInstanceOf(PasscodeUser::class, $user);
            $password = $this->passcodeGenerator->getPasswordForUser($user);
        }

        $this->browser->request('GET', "https://{$_ENV['FRONTEND_HOSTNAME']}/login");
        $this->browser->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][0]' => $username,
            'passcode_login[passcode][1]' => $password,
        ]);

        $url = parse_url($this->browser->getCrawler()->getUri());

        // has the user been redirected back to login (failed attempt)?
        $assertFunc = $expectedValidLogin ? 'assertNotEquals' : 'assertEquals';
        self::$assertFunc('/login', $url['path']);
    }
}