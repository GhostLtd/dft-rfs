<?php

namespace App\Tests\Utility\Notification;

use App\Utility\Notification\SmsRecipientDailyCap;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SmsRecipientDailyCapTest extends KernelTestCase
{
    private const string NOW = '2026-09-10 09:00:00 Europe/London';
    private const string SECRET = 'test-secret';
    private const string MOBILE = '07700900123';
    private Connection $connection;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
        $this->connection = $container->get(EntityManagerInterface::class)->getConnection();
    }

    private function cap(int|string|null $cap = 5, string $secret = self::SECRET, ?LoggerInterface $logger = null): SmsRecipientDailyCap
    {
        return new SmsRecipientDailyCap($this->connection, $logger ?? new NullLogger(), $cap, $secret);
    }

    private static function at(string $when): \DateTimeImmutable
    {
        return new \DateTimeImmutable($when);
    }

    private function sent(string $mobile, string $day, string $secret = self::SECRET): ?int
    {
        $sent = $this->connection->fetchOne('SELECT sent FROM sms_recipient_day WHERE recipient_hmac = ? AND day = ?', [hash_hmac('sha256', $mobile, $secret), $day]);
        return $sent === false ? null : (int) $sent;
    }

    #[DataProvider('londonDays')]
    public function testDayIsTheLondonCalendarDay(string $at, string $expected): void
    {
        self::assertSame($expected, SmsRecipientDailyCap::day(self::at($at)));
    }

    public static function londonDays(): array
    {
        return [
            'London evening' => ['2026-09-10 23:59:59 Europe/London', '2026-09-10'],
            '23:30 UTC in BST is already tomorrow' => ['2026-09-10 23:30:00 UTC', '2026-09-11'],
            'last BST night: 23:30 UTC is 00:30 on the 25th' => ['2026-10-24 23:30:00 UTC', '2026-10-25'],
            'first GMT night: 23:30 UTC is still the 25th' => ['2026-10-25 23:30:00 UTC', '2026-10-25'],
            'winter' => ['2027-01-15 23:59:00 Europe/London', '2027-01-15'],
        ];
    }

    public function testFirstConsumeOfTheDayInsertsTheRow(): void
    {
        self::assertTrue($this->cap()->tryConsume(self::MOBILE, self::at(self::NOW)));
        self::assertSame(1, $this->sent(self::MOBILE, '2026-09-10'));
    }

    public function testConsumesUpToTheCapThenRefuses(): void
    {
        $cap = $this->cap(2);
        self::assertTrue($cap->tryConsume(self::MOBILE, self::at(self::NOW)));
        self::assertTrue($cap->tryConsume(self::MOBILE, self::at(self::NOW)));
        self::assertFalse($cap->tryConsume(self::MOBILE, self::at(self::NOW)));
        self::assertSame(2, $this->sent(self::MOBILE, '2026-09-10'));
    }

    public function testNumbersAndDaysAreCountedSeparately(): void
    {
        $cap = $this->cap(1);
        self::assertTrue($cap->tryConsume(self::MOBILE, self::at(self::NOW)));
        self::assertTrue($cap->tryConsume('07700900999', self::at(self::NOW)), 'another number has its own count');
        self::assertTrue($cap->tryConsume(self::MOBILE, self::at('2026-09-11 00:00:00 Europe/London')), 'a new London day starts at zero');
        self::assertFalse($cap->tryConsume(self::MOBILE, self::at('2026-09-11 08:00:00 Europe/London')));
    }

    public function testZeroCapAlwaysRefuses(): void
    {
        self::assertFalse($this->cap(0)->tryConsume(self::MOBILE, self::at(self::NOW)));
        self::assertNull($this->sent(self::MOBILE, '2026-09-10'));
    }

    #[DataProvider('emptyCaps')]
    public function testEmptyCapUsesTheDefault(string|null $cap): void
    {
        $dailyCap = $this->cap($cap);
        self::assertSame(SmsRecipientDailyCap::DEFAULT_CAP, $dailyCap->effectiveCap());
        foreach (range(1, SmsRecipientDailyCap::DEFAULT_CAP) as $_) {
            self::assertTrue($dailyCap->tryConsume(self::MOBILE, self::at(self::NOW)));
        }
        self::assertFalse($dailyCap->tryConsume(self::MOBILE, self::at(self::NOW)));
    }

    public static function emptyCaps(): array
    {
        return ['null' => [null], 'empty string' => [''], 'whitespace' => ['  ']];
    }

    public function testNumericStringCapBehavesAsThatCap(): void
    {
        $cap = $this->cap(' 1 ');
        self::assertSame(1, $cap->effectiveCap());
        self::assertTrue($cap->tryConsume(self::MOBILE, self::at(self::NOW)));
        self::assertFalse($cap->tryConsume(self::MOBILE, self::at(self::NOW)));
    }

    #[DataProvider('invalidCaps')]
    public function testInvalidCapRefusesWithoutTouchingTheDbAndLogsAnError(int|string $cap): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with("Notifications: APP_SMS_DAILY_CAP_PER_NUMBER invalid: '{$cap}' — no SMS will be sent");

        $dailyCap = $this->cap($cap, logger: $logger);
        self::assertSame(0, $dailyCap->effectiveCap());
        self::assertFalse($dailyCap->tryConsume(self::MOBILE, self::at(self::NOW)));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM sms_recipient_day'));
    }

    public static function invalidCaps(): array
    {
        return ['comma' => ['5,0'], 'word' => ['five'], 'negative string' => ['-1'], 'negative int' => [-1]];
    }

    public function testConsumeRollsBackWithItsTransaction(): void
    {
        $this->connection->beginTransaction();
        self::assertTrue($this->cap()->tryConsume(self::MOBILE, self::at(self::NOW)));
        $this->connection->rollBack();
        self::assertNull($this->sent(self::MOBILE, '2026-09-10'));
    }

    public function testRemaining(): void
    {
        $cap = $this->cap(5);
        self::assertSame(5, $cap->remaining(self::MOBILE, self::at(self::NOW)));
        $cap->tryConsume(self::MOBILE, self::at(self::NOW));
        $cap->tryConsume(self::MOBILE, self::at(self::NOW));
        self::assertSame(3, $cap->remaining(self::MOBILE, self::at(self::NOW)));
        self::assertSame(8, $cap->remaining(self::MOBILE, self::at(self::NOW), 10), 'override cap');
        self::assertSame(0, $cap->remaining(self::MOBILE, self::at(self::NOW), 1), 'never negative');
        self::assertSame(5, $cap->remaining('07700900999', self::at(self::NOW)), 'another number');
        self::assertSame(0, $this->cap('five')->remaining(self::MOBILE, self::at(self::NOW)), 'an invalid cap has nothing remaining');
    }

    public function testLoweringTheCapBelowTodaysCountRefusesAndRemainingIsZero(): void
    {
        $five = $this->cap(5);
        foreach (range(1, 3) as $_) {
            $five->tryConsume(self::MOBILE, self::at(self::NOW));
        }

        $two = $this->cap(2);
        self::assertFalse($two->tryConsume(self::MOBILE, self::at(self::NOW)));
        self::assertSame(0, $two->remaining(self::MOBILE, self::at(self::NOW)));
        self::assertSame(3, $this->sent(self::MOBILE, '2026-09-10'), 'unchanged by the refusal');
    }

    public function testPruneBeforeDeletesOnlyEarlierDays(): void
    {
        $cap = $this->cap();
        $cap->tryConsume(self::MOBILE, self::at('2026-09-08 09:00:00 Europe/London'));
        $cap->tryConsume(self::MOBILE, self::at('2026-09-09 23:59:00 Europe/London'));
        $cap->tryConsume(self::MOBILE, self::at(self::NOW));

        self::assertSame(2, $cap->pruneBefore(self::at(self::NOW)));
        self::assertSame(['2026-09-10'], $this->connection->fetchFirstColumn('SELECT day FROM sms_recipient_day'));
    }

    public function testIsConfigValid(): void
    {
        self::assertTrue($this->cap(5)->isConfigValid());
        self::assertFalse($this->cap('five')->isConfigValid());
        self::assertTrue($this->cap(0)->isConfigValid(), 'a configured 0 is a valid value, just an emergency stop');
    }

    public function testStoredKeyIsAnHmacOfTheNumberNotTheNumber(): void
    {
        $this->cap()->tryConsume(self::MOBILE, self::at(self::NOW));
        $this->cap(secret: 'another-secret')->tryConsume(self::MOBILE, self::at(self::NOW));

        $keys = $this->connection->fetchFirstColumn('SELECT recipient_hmac FROM sms_recipient_day ORDER BY recipient_hmac');
        self::assertCount(2, $keys, 'a different secret gives a different key');
        self::assertContains(hash_hmac('sha256', self::MOBILE, self::SECRET), $keys);
        foreach ($keys as $key) {
            self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $key);
            self::assertStringNotContainsString('7700900123', $key);
        }
    }
}
