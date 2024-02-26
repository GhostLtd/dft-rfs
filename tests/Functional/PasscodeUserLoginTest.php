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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->browser->followRedirects(true);

        $container = static::getContainer()->get('test.service_container');
        $this->passcodeGenerator = $container->get(PasscodeGenerator::class);
    }

    protected function getMangler(array $options): \Closure {
        return function(string $str) use ($options): string {
            if ($options['insert'] ?? null) {
                $midpoint = intval(strlen($str) / 2);
                $firstHalf = substr($str, 0, $midpoint);
                $secondHalf = substr($str, $midpoint);

                $str = "{$firstHalf}{$options['insert']}{$secondHalf}";
            }

            if ($options['prefix'] ?? null) {
                $str = "{$options['prefix']}{$str}";
            }

            if ($options['postfix'] ?? null) {
                $str = "{$str}{$options['postfix']}";
            }

            return $str;
        };
    }

    public function dataProvider(): array
    {
        // Q: Why do we use the mangler rather than directly coding the mangled strings
        // A: When password is set to null here, we use the auto-generated password which we don't know ahead of time.
        return [
            ['user:frontend', 'test', true],
            ['user:frontend', null, true],
            ['user:frontend', 'password', false],
            ['user:frontend:null-password', null, true],
            ['user:frontend:null-password', 'password', false],
            ['other', 'test', false],

            // Test options for mangling the username (hash, space, dash at various points)
            ['user:frontend', 'test', true, $this->getMangler(['prefix' => '#'])],
            ['user:frontend', 'test', true, $this->getMangler(['prefix' => ' '])],
            ['user:frontend', 'test', true, $this->getMangler(['prefix' => '-'])],
            ['user:frontend', 'test', true, $this->getMangler(['postfix' => '#'])],
            ['user:frontend', 'test', true, $this->getMangler(['postfix' => ' '])],
            ['user:frontend', 'test', true, $this->getMangler(['postfix' => '-'])],
            ['user:frontend', 'test', true, $this->getMangler(['insert' => '#'])],
            ['user:frontend', 'test', true, $this->getMangler(['insert' => ' '])],
            ['user:frontend', 'test', true, $this->getMangler(['insert' => '-'])],
            ['user:frontend', 'test', true, $this->getMangler(['prefix' => '- #', 'postfix' => ' #-', 'insert' => '#- '])],

            ['user:frontend:null-password', null, true, $this->getMangler(['prefix' => '#'])],
            ['user:frontend:null-password', null, true, $this->getMangler(['prefix' => ' '])],
            ['user:frontend:null-password', null, true, $this->getMangler(['prefix' => '-'])],
            ['user:frontend:null-password', null, true, $this->getMangler(['postfix' => '#'])],
            ['user:frontend:null-password', null, true, $this->getMangler(['postfix' => ' '])],
            ['user:frontend:null-password', null, true, $this->getMangler(['postfix' => '-'])],
            ['user:frontend:null-password', null, true, $this->getMangler(['insert' => '#'])],
            ['user:frontend:null-password', null, true, $this->getMangler(['insert' => ' '])],
            ['user:frontend:null-password', null, true, $this->getMangler(['insert' => '-'])],
            ['user:frontend:null-password', null, true, $this->getMangler(['prefix' => '- #', 'postfix' => ' #-', 'insert' => '#- '])],

            // Now do the same but with the password
            ['user:frontend', 'test', true, null, $this->getMangler(['prefix' => '#'])],
            ['user:frontend', 'test', true, null, $this->getMangler(['prefix' => ' '])],
            ['user:frontend', 'test', true, null, $this->getMangler(['prefix' => '-'])],
            ['user:frontend', 'test', true, null, $this->getMangler(['postfix' => '#'])],
            ['user:frontend', 'test', true, null, $this->getMangler(['postfix' => ' '])],
            ['user:frontend', 'test', true, null, $this->getMangler(['postfix' => '-'])],
            ['user:frontend', 'test', true, null, $this->getMangler(['insert' => '#'])],
            ['user:frontend', 'test', true, null, $this->getMangler(['insert' => ' '])],
            ['user:frontend', 'test', true, null, $this->getMangler(['insert' => '-'])],
            ['user:frontend', 'test', true, null, $this->getMangler(['prefix' => '- #', 'postfix' => ' #-', 'insert' => '#- '])],

            ['user:frontend:null-password', null, true, null, $this->getMangler(['prefix' => '#'])],
            ['user:frontend:null-password', null, true, null, $this->getMangler(['prefix' => ' '])],
            ['user:frontend:null-password', null, true, null, $this->getMangler(['prefix' => '-'])],
            ['user:frontend:null-password', null, true, null, $this->getMangler(['postfix' => '#'])],
            ['user:frontend:null-password', null, true, null, $this->getMangler(['postfix' => ' '])],
            ['user:frontend:null-password', null, true, null, $this->getMangler(['postfix' => '-'])],
            ['user:frontend:null-password', null, true, null, $this->getMangler(['insert' => '#'])],
            ['user:frontend:null-password', null, true, null, $this->getMangler(['insert' => ' '])],
            ['user:frontend:null-password', null, true, null, $this->getMangler(['insert' => '-'])],
            ['user:frontend:null-password', null, true, null, $this->getMangler(['prefix' => '- #', 'postfix' => ' #-', 'insert' => '#- '])],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testLogin(
        string    $usernameOrRef,
        ?string   $password,
        bool      $expectedValidLogin,
        ?\Closure $usernameMangler = null,
        ?\Closure $passwordMangler = null,
    )
    {
        $this->loadFixtures([PasscodeLoginFixtures::class]);

        if ($this->fixtureReferenceRepository->hasReference($usernameOrRef, PasscodeUser::class)) {
            /** @var PasscodeUser $user */
            $user = $this->fixtureReferenceRepository->getReference($usernameOrRef, PasscodeUser::class);
            $username = $user->getUsername();
        } else {
            $username = $usernameOrRef;
            $user = null;
        }

        $username = $usernameMangler ? $usernameMangler($username) : $username;

        // If dataProvider password is null, use regenerated one
        if ($password === null) {
            $this->assertInstanceOf(PasscodeUser::class, $user);
            $password = $this->passcodeGenerator->getPasswordForUser($user);
        }

        $password = $passwordMangler ? $passwordMangler($password) : $password;

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