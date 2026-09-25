<?php

namespace App\Tests\Functional;

use App\Entity\PasscodeUser;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use App\Tests\DataFixtures\UserFixtures;
use App\Utility\LoginLinkToken;
use App\Utility\PasscodeGenerator;
use PHPUnit\Framework\Attributes\DataProvider;

class ShortLoginLinkTest extends AbstractFunctionalTestCase
{
    private const string CODE_1 = '12345678';

    private function frontend(string $path): string
    {
        return "https://{$_ENV['FRONTEND_HOSTNAME']}{$path}";
    }

    private function codeInputs(): array
    {
        $crawler = $this->browser->getCrawler();
        return [
            $crawler->filter('input[name="passcode_login[passcode][0]"]')->attr('value'),
            $crawler->filter('input[name="passcode_login[passcode][1]"]')->attr('value'),
        ];
    }

    public function testShortLinkRedirectsToLoginWithTheTokenUntouched(): void
    {
        $this->browser->followRedirects(false);
        $this->browser->request('GET', $this->frontend('/c/0pnfq'));

        self::assertResponseRedirects('/login?c=0pnfq');
    }

    public function testShortLinkOverHttpIsRedirectedToHttps(): void
    {
        $this->browser->followRedirects(false);
        $this->browser->request('GET', "http://{$_ENV['FRONTEND_HOSTNAME']}/c/0pnfq");

        self::assertResponseRedirects($this->frontend('/c/0pnfq'));
    }

    public function testValidTokenPrefillsCodeOneOnly(): void
    {
        $this->browser->request('GET', $this->frontend('/login?c=0pnfq'));

        self::assertResponseIsSuccessful();
        self::assertSame(['1234-5678', null], $this->codeInputs());
    }

    #[DataProvider('invalidTokens')]
    public function testInvalidTokenGivesTheEmptyFormWithoutAnError(string $query): void
    {
        $this->browser->request('GET', $this->frontend("/login{$query}"));

        self::assertResponseIsSuccessful();
        self::assertSame([null, null], $this->codeInputs());
        self::assertSelectorNotExists('.govuk-error-message');
        self::assertSelectorNotExists('.govuk-error-summary');
    }

    public static function invalidTokens(): iterable
    {
        yield 'too large' => ['?c=zzzzz'];
        yield 'garbage' => ['?c=%25%25'];
        yield 'truncated' => ['?c=0pn'];
        yield 'no token' => [''];
    }

    public function testFollowingTheLinkAndTypingCodeTwoLogsIn(): void
    {
        $this->loadFixtures([UserFixtures::class, SurveyFixtures::class]);

        $user = $this->fixtureReferenceRepository->getReference('user:frontend', PasscodeUser::class);
        $entityManager = $this->getEntityManager();
        $entityManager->find(PasscodeUser::class, $user->getId())->setUsername(self::CODE_1);
        $entityManager->flush();

        $password = static::getContainer()->get(PasscodeGenerator::class)->getPasswordForUser($user);

        $this->browser->request('GET', $this->frontend('/c/'.LoginLinkToken::encode(self::CODE_1)));
        $this->browser->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][1]' => $password, // code 1 as prefilled by the link
        ]);

        self::assertStringStartsWith('/domestic-survey', parse_url($this->browser->getCrawler()->getUri(), PHP_URL_PATH));
    }
}
