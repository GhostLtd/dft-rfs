<?php

namespace App\Tests\Utility;

use App\Repository\EmailBlacklistRepository;
use App\Utility\EmailBlacklistService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EmailBlacklistServiceTest extends TestCase
{
    private EmailBlacklistRepository&MockObject $repository;
    private LoggerInterface&MockObject $logger;
    private EmailBlacklistService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EmailBlacklistRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new EmailBlacklistService($this->repository, $this->logger);
    }

    public function testIsBlacklisted(): void
    {
        $email = 'blocked@example.com';

        $this->repository
            ->expects($this->once())
            ->method('isBlacklisted')
            ->with($email)
            ->willReturn(true);

        $result = $this->service->isBlacklisted($email);

        $this->assertTrue($result);
    }

    public function testIsBlacklistedLogsWhenTrue(): void
    {
        $email = 'blocked@example.com';

        $this->repository
            ->method('isBlacklisted')
            ->with($email)
            ->willReturn(true);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Email is blacklisted: {email}',
                ['email' => $email]
            );

        $this->service->isBlacklisted($email);
    }

    public function testIsBlacklistedDoesNotLogWhenFalse(): void
    {
        $email = 'allowed@example.com';

        $this->repository
            ->method('isBlacklisted')
            ->with($email)
            ->willReturn(false);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $result = $this->service->isBlacklisted($email);

        $this->assertFalse($result);
    }

    public function testFilterBlacklistedEmails(): void
    {
        $emails = [
            'allowed1@example.com',
            'blocked@example.com',
            'allowed2@example.com',
        ];

        $expected = [
            'allowed1@example.com',
            'allowed2@example.com',
        ];

        $this->repository
            ->expects($this->once())
            ->method('filterBlacklistedEmails')
            ->with($emails)
            ->willReturn($expected);

        $result = $this->service->filterBlacklistedEmails($emails);

        $this->assertSame($expected, $result);
    }

    public function testFilterBlacklistedEmailsLogsRemovedCount(): void
    {
        $emails = [
            'allowed1@example.com',
            'blocked1@example.com',
            'blocked2@example.com',
            'allowed2@example.com',
        ];

        $filtered = [
            'allowed1@example.com',
            'allowed2@example.com',
        ];

        $this->repository
            ->method('filterBlacklistedEmails')
            ->with($emails)
            ->willReturn($filtered);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Filtered {count} blacklisted emails',
                ['count' => 2]
            );

        $this->service->filterBlacklistedEmails($emails);
    }

    public function testFilterBlacklistedEmailsDoesNotLogWhenNoneRemoved(): void
    {
        $emails = [
            'allowed1@example.com',
            'allowed2@example.com',
        ];

        $this->repository
            ->method('filterBlacklistedEmails')
            ->with($emails)
            ->willReturn($emails);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->service->filterBlacklistedEmails($emails);
    }

    public function testGetBlacklistedFromList(): void
    {
        $emails = [
            'allowed1@example.com',
            'blocked1@example.com',
            'blocked2@example.com',
            'allowed2@example.com',
        ];

        $expected = [
            'blocked1@example.com',
            'blocked2@example.com',
        ];

        $this->repository
            ->expects($this->once())
            ->method('getBlacklistedFromList')
            ->with($emails)
            ->willReturn($expected);

        $result = $this->service->getBlacklistedFromList($emails);

        $this->assertSame($expected, $result);
    }

    public function testGetBlacklistedFromListReturnsEmpty(): void
    {
        $emails = [
            'allowed1@example.com',
            'allowed2@example.com',
        ];

        $this->repository
            ->expects($this->once())
            ->method('getBlacklistedFromList')
            ->with($emails)
            ->willReturn([]);

        $result = $this->service->getBlacklistedFromList($emails);

        $this->assertSame([], $result);
    }

    public function testFilterBlacklistedEmailsWithEmptyArray(): void
    {
        $emails = [];

        $this->repository
            ->expects($this->once())
            ->method('filterBlacklistedEmails')
            ->with($emails)
            ->willReturn([]);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $result = $this->service->filterBlacklistedEmails($emails);

        $this->assertSame([], $result);
    }

    public function testGetBlacklistedFromListWithEmptyArray(): void
    {
        $emails = [];

        $this->repository
            ->expects($this->once())
            ->method('getBlacklistedFromList')
            ->with($emails)
            ->willReturn([]);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $result = $this->service->getBlacklistedFromList($emails);

        $this->assertSame([], $result);
    }
}
