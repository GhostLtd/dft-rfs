<?php

namespace App\Tests\Repository;

use App\Entity\EmailBlacklist;
use App\Repository\EmailBlacklistRepository;
use App\Tests\DataFixtures\TestSpecific\EmailBlacklistRepositoryFixtures;

class EmailBlacklistRepositoryTest extends AbstractRepositoryTest
{
    public function dataIsBlacklisted(): array
    {
        return [
            'exact match lowercase' => ['blocked@example.com', true],
            'exact match uppercase' => ['BLOCKED@EXAMPLE.COM', true],
            'exact match mixed case' => ['Blocked@Example.Com', true],
            'leading whitespace' => [' blocked@example.com', true],
            'trailing whitespace' => ['blocked@example.com ', true],
            'leading and trailing whitespace' => [' blocked@example.com ', true],
            'not blacklisted' => ['notblocked@example.com', false],
            'spammer lowercase' => ['spammer@bad.com', true],
            'spammer uppercase' => ['SPAMMER@BAD.COM', true],
            'allowed email' => ['allowed@example.com', false],
            'empty string' => ['', false],
        ];
    }

    /**
     * @dataProvider dataIsBlacklisted
     */
    public function testIsBlacklisted(string $email, bool $expected): void
    {
        $this->loadFixtures([EmailBlacklistRepositoryFixtures::class]);
        $repo = $this->getRepository(EmailBlacklist::class);

        $this->assertInstanceOf(EmailBlacklistRepository::class, $repo);

        $this->assertEquals($expected, $repo->isBlacklisted($email));
    }

    public function dataFilterBlacklistedEmails(): array
    {
        return [
            'mixed blocked and allowed' => [
                ['blocked@example.com', 'allowed@example.com', 'spammer@bad.com'],
                ['allowed@example.com'],
            ],
            'all blocked' => [
                ['blocked@example.com', 'spammer@bad.com', 'BLOCKED@EXAMPLE.COM'],
                [],
            ],
            'all allowed' => [
                ['allowed@example.com', 'notblocked@example.com', 'another@allowed.com'],
                ['allowed@example.com', 'notblocked@example.com', 'another@allowed.com'],
            ],
            'empty array' => [
                [],
                [],
            ],
            'case insensitive filtering' => [
                ['BLOCKED@EXAMPLE.COM', 'allowed@example.com', 'SPAMMER@BAD.COM'],
                ['allowed@example.com'],
            ],
            'whitespace handling' => [
                [' blocked@example.com ', 'allowed@example.com', ' spammer@bad.com'],
                ['allowed@example.com'],
            ],
        ];
    }

    /**
     * @dataProvider dataFilterBlacklistedEmails
     */
    public function testFilterBlacklistedEmails(array $emails, array $expected): void
    {
        $this->loadFixtures([EmailBlacklistRepositoryFixtures::class]);
        $repo = $this->getRepository(EmailBlacklist::class);

        $this->assertInstanceOf(EmailBlacklistRepository::class, $repo);

        $result = $repo->filterBlacklistedEmails($emails);

        $this->assertEquals($expected, $result);
    }

    public function dataGetBlacklistedFromList(): array
    {
        return [
            'mixed blocked and allowed' => [
                ['blocked@example.com', 'allowed@example.com', 'SPAMMER@BAD.COM'],
                ['blocked@example.com', 'spammer@bad.com'],
            ],
            'all allowed' => [
                ['allowed@example.com', 'notblocked@example.com'],
                [],
            ],
            'all blocked' => [
                ['blocked@example.com', 'SPAMMER@BAD.COM'],
                ['blocked@example.com', 'spammer@bad.com'],
            ],
            'empty array' => [
                [],
                [],
            ],
            'case normalization' => [
                ['BLOCKED@EXAMPLE.COM', 'SpAmMeR@bAd.CoM'],
                ['blocked@example.com', 'spammer@bad.com'],
            ],
            'whitespace trimming and normalization' => [
                [' blocked@example.com ', ' SPAMMER@BAD.COM '],
                ['blocked@example.com', 'spammer@bad.com'],
            ],
            'duplicates removed and normalized' => [
                ['blocked@example.com', 'BLOCKED@EXAMPLE.COM', 'blocked@example.com'],
                ['blocked@example.com'],
            ],
        ];
    }

    /**
     * @dataProvider dataGetBlacklistedFromList
     */
    public function testGetBlacklistedFromList(array $emails, array $expected): void
    {
        $this->loadFixtures([EmailBlacklistRepositoryFixtures::class]);
        $repo = $this->getRepository(EmailBlacklist::class);

        $this->assertInstanceOf(EmailBlacklistRepository::class, $repo);

        $result = $repo->getBlacklistedFromList($emails);

        $this->assertEquals($expected, $result);
    }
}
