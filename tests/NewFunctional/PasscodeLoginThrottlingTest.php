<?php

namespace App\Tests\NewFunctional;

use App\Entity\PasscodeUser;
use App\Tests\DataFixtures\Dast\RoRoReferenceFixtures;
use App\Utility\Dev\DastSeeder;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Passcode login is throttled (the main firewall's login_throttling): 5 attempts a minute per username and IP. Without
 * it, the second code (100 million values) could be brute-forced for a known first code (found by DAST:
 * local/2026-11-17-dast-bug-log.md, B12). All requests here come from the same IP.
 */
class PasscodeLoginThrottlingTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([RoRoReferenceFixtures::class], ['hostname' => 'rfs-frontend.localhost']);
        static::getContainer()->get(DastSeeder::class)->seed();
    }

    public function testRightPasscodeIsRefusedAfterFiveFailures(): void
    {
        $username = DastSeeder::USERNAMES['domestic'];
        for ($i = 1; $i <= 5; $i++) {
            $this->login($username, '00000000');
            $this->assertPathMatches('/login', false, "Wrong attempt {$i} goes back to the login page");
            $this->assertStringNotContainsString('Too many', $this->pageText(), "Attempt {$i} isn't throttled yet");
        }

        $this->login($username, $this->passwordFor($username));

        $this->assertPathMatches('/login', false, 'The right passcode is refused once throttled');
        $this->assertStringContainsString('Too many failed attempts to sign in', $this->pageText());
        // Symfony rounds the wait up to whole minutes, so it can say 2 for a 1-minute interval
        $this->assertMatchesRegularExpression('/Wait \\d+ minutes? and try again/', $this->pageText(), 'The wait is filled in');
    }

    public function testAnotherUserFromTheSameIpIsNotThrottled(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->login(DastSeeder::USERNAMES['domestic'], '00000000');
        }

        $username = DastSeeder::USERNAMES['international'];
        $this->login($username, $this->passwordFor($username));

        $this->assertPathMatches('/international-survey', false);
    }

    /** Control: a few mistakes don't stop the right passcode */
    public function testRightPasscodeAfterFourFailuresLogsIn(): void
    {
        $username = DastSeeder::USERNAMES['domestic'];
        for ($i = 1; $i <= 4; $i++) {
            $this->login($username, '00000000');
        }

        $this->login($username, $this->passwordFor($username));

        $this->assertPathMatches('/domestic-survey', false);
    }

    private function login(string $username, string $password): void
    {
        $this->client->getCookieJar()->clear();
        $this->client->request('GET', '/login');
        $this->client->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][0]' => $username,
            'passcode_login[passcode][1]' => $password,
        ]);
    }

    private function passwordFor(string $username): string
    {
        $user = static::getContainer()->get(EntityManagerInterface::class)->getRepository(PasscodeUser::class)->findOneBy(['username' => $username]);
        return static::getContainer()->get(PasscodeGenerator::class)->getPasswordForUser($user);
    }

    private function pageText(): string
    {
        return $this->client->getCrawler()->filter('body')->text();
    }
}
