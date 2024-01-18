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

    protected function setUp(): void
    {
        parent::setUp();
        $this->browser->followRedirects(true);

        $container = static::getContainer()->get('test.service_container');
        $this->passcodeGenerator = $container->get(PasscodeGenerator::class);
    }

    public function dataProvider(): array
    {
        return [
            ['user:frontend', 'test', true, false, false, false, false],
            ['user:frontend', null, true, false, false, false, false],
            ['user:frontend', 'password', false, false, false, false, false],
            ['user:frontend:null-password', null, true, false, false, false, false],
            ['user:frontend:null-password', 'password', false, false, false, false, false],
            ['other', 'test', false, false, false, false, false],

            // Test options for mangling the input (prefixed hash, or spaces in the password)
            ['user:frontend', 'test', true, true, false, false, false],
            ['user:frontend', 'test', true, false, true, false, false],
            ['user:frontend', 'test', true, true, true, false, false],

            ['user:frontend:null-password', null, true, true, false, false, false],
            ['user:frontend:null-password', null, true, false, true, false, false],
            ['user:frontend:null-password', null, true, true, true, false, false],

            // Now do the same but with the username
            ['user:frontend', 'test', true, false, false, true, false],
            ['user:frontend', 'test', true, false, false, false, true],
            ['user:frontend', 'test', true, false, false, true, true],

            ['user:frontend:null-password', null, true, false, false, true, false],
            ['user:frontend:null-password', null, true, false, false, false, true],
            ['user:frontend:null-password', null, true, false, false, true, true],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testLogin(
        string  $usernameOrRef,
        ?string $password,
        bool    $expectedValidLogin,
        bool    $modifyPasswordByPrefixingHash,
        bool    $modifyPasswordByAddingSpace,
        bool    $modifyUsernameByPrefixingHash,
        bool    $modifyUsernameByAddingSpace,
    )
    {
        $this->loadFixtures([PasscodeLoginFixtures::class]);

        $mangle = function(string $str, bool $prefixHash, bool $addSpace): string
        {
            if ($addSpace) {
                $midpoint = intval(strlen($str) / 2);
                $firstHalf = substr($str, 0, $midpoint);
                $secondHalf = substr($str, $midpoint);

                $str = "{$firstHalf} {$secondHalf}";
            }

            if ($prefixHash) {
                $str = "#{$str}";
            }

            return $str;
        };

        if ($this->fixtureReferenceRepository->hasReference($usernameOrRef)) {
            /** @var PasscodeUser $user */
            $user = $this->fixtureReferenceRepository->getReference($usernameOrRef);
            $username = $user->getUsername();
        } else {
            $username = $usernameOrRef;
            $user = null;
        }

        $username = $mangle($username, $modifyUsernameByPrefixingHash, $modifyUsernameByAddingSpace);

        // If dataProvider password is null, use regenerated one
        if ($password === null) {
            self::assertInstanceOf(PasscodeUser::class, $user);
            $password = $this->passcodeGenerator->getPasswordForUser($user);
        }

        $password = $mangle($password, $modifyPasswordByPrefixingHash, $modifyPasswordByAddingSpace);

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