<?php

namespace App\Tests\Utility\Notification;

use App\Utility\Notification\SmsAllowance;
use App\Utility\Notification\SmsAttemptRecord;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SmsAllowanceTest extends KernelTestCase
{
    private const string NOW = '2026-09-10 09:00:00 Europe/London';
    private Connection $connection;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
        $this->connection = $container->get(EntityManagerInterface::class)->getConnection();
    }

    private function allowance(int|string|null $cap): SmsAllowance
    {
        return new SmsAllowance($this->connection, new NullLogger(), $cap);
    }

    private static function at(string $when): \DateTimeImmutable
    {
        return new \DateTimeImmutable($when);
    }

    private function sent(int $financialYear): ?int
    {
        $sent = $this->connection->fetchOne('SELECT sent FROM sms_usage WHERE financial_year = ?', [$financialYear]);
        return $sent === false ? null : (int) $sent;
    }

    private function attempts(string $notificationId): ?int
    {
        $attempts = $this->connection->fetchOne('SELECT attempts FROM sms_attempt WHERE notification_id = ?', [$notificationId]);
        return $attempts === false ? null : (int) $attempts;
    }

    #[DataProvider('financialYears')]
    public function testFinancialYear(string $at, int $expected): void
    {
        self::assertSame($expected, $this->allowance(1)->financialYear(self::at($at)));
    }

    public static function financialYears(): array
    {
        return [
            'last minute of March' => ['2027-03-31 23:59:00 Europe/London', 2026],
            'first minute of April (BST)' => ['2027-04-01 00:00:00 Europe/London', 2027],
            '31 March 23:30 UTC is already 1 April in London' => ['2027-03-31 23:30:00 UTC', 2027],
            'January belongs to the previous year' => ['2027-01-15 12:00:00 Europe/London', 2026],
            'September' => [self::NOW, 2026],
        ];
    }

    public function testFirstConsumeOfTheYearInsertsTheRow(): void
    {
        self::assertTrue($this->allowance(3)->tryConsume(self::at(self::NOW)));
        self::assertSame(1, $this->sent(2026));
    }

    public function testConsumesUpToTheCapThenRefuses(): void
    {
        $allowance = $this->allowance(2);
        self::assertTrue($allowance->tryConsume(self::at(self::NOW)));
        self::assertTrue($allowance->tryConsume(self::at(self::NOW)));
        self::assertFalse($allowance->tryConsume(self::at(self::NOW)));
        self::assertSame(2, $this->sent(2026));
    }

    public function testZeroCapAlwaysRefuses(): void
    {
        self::assertFalse($this->allowance(0)->tryConsume(self::at(self::NOW)));
        self::assertNull($this->sent(2026));
    }

    public function testNewFinancialYearStartsAtZero(): void
    {
        $allowance = $this->allowance(1);
        self::assertTrue($allowance->tryConsume(self::at('2027-03-31 23:59:00 Europe/London')));
        self::assertFalse($allowance->tryConsume(self::at('2027-03-31 23:59:30 Europe/London')));
        self::assertTrue($allowance->tryConsume(self::at('2027-04-01 00:00:00 Europe/London')));
        self::assertSame(1, $this->sent(2026));
        self::assertSame(1, $this->sent(2027));
    }

    public function testConsumeRollsBackWithItsTransaction(): void
    {
        $this->connection->beginTransaction();
        self::assertTrue($this->allowance(5)->tryConsume(self::at(self::NOW)));
        $this->connection->rollBack();
        self::assertNull($this->sent(2026));
    }

    public function testRecordReservationWritesTheLondonDay(): void
    {
        $this->allowance(5)->recordReservation('n-1', self::at('2026-09-10 23:30:00 UTC'));

        self::assertSame('2026-09-11', $this->connection->fetchOne('SELECT reserved_on FROM sms_attempt WHERE notification_id = ?', ['n-1']), '23:30 UTC is 00:30 BST');
        self::assertSame(0, $this->attempts('n-1'));
    }

    public function testRecordAttemptIncrementsAndReturnsTheLedgerRow(): void
    {
        $allowance = $this->allowance(5);
        $allowance->recordReservation('n-1', self::at(self::NOW));

        self::assertEquals(new SmsAttemptRecord(1, 2026, '2026-09-10', false), $allowance->recordAttempt('n-1', self::at(self::NOW)));
        self::assertSame(2, $allowance->recordAttempt('n-1', self::at(self::NOW))->attempts);
        self::assertNull($this->sent(2026), 'recording an attempt charges nothing; the middleware decides');
    }

    public function testRecordAttemptWithNoLedgerRowInsertsOne(): void
    {
        self::assertEquals(new SmsAttemptRecord(1, 2026, null, true), $this->allowance(5)->recordAttempt('ghost', self::at(self::NOW)));
        self::assertSame(1, $this->attempts('ghost'));
        self::assertNull($this->sent(2026));
    }

    #[DataProvider('emptyCaps')]
    public function testEmptyCapConsumesNormallyWithDefaultSemantics(int|string|null $cap): void
    {
        self::assertTrue($this->allowance($cap)->tryConsume(self::at(self::NOW)));
        self::assertSame(1, $this->sent(2026));
    }

    public static function emptyCaps(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'blank string' => ['  '],
        ];
    }

    #[DataProvider('numericStringCaps')]
    public function testNumericStringCapBehavesAsThatCap(string $cap): void
    {
        $allowance = $this->allowance($cap);
        $this->connection->insert('sms_usage', ['financial_year' => 2026, 'sent' => 149]);

        self::assertTrue($allowance->tryConsume(self::at(self::NOW)), 'consume up to the cap of 150');
        self::assertSame(150, $this->sent(2026));
        self::assertFalse($allowance->tryConsume(self::at(self::NOW)), 'cap of 150 reached');
    }

    public static function numericStringCaps(): array
    {
        return [
            'plain numeric string' => ['150'],
            'numeric string with whitespace' => [' 150 '],
        ];
    }

    #[DataProvider('invalidCaps')]
    public function testInvalidCapRefusesWithoutTouchingTheDbAndLogsOnce(string $cap): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with("Notifications: APP_SMS_ANNUAL_CAP invalid: '{$cap}' — no SMS will be sent");
        $allowance = new SmsAllowance($this->connection, $logger, $cap);

        self::assertFalse($allowance->tryConsume(self::at(self::NOW)));
        self::assertNull($this->sent(2026));
    }

    public static function invalidCaps(): array
    {
        return [
            'comma separated' => ['20,000'],
            'unit suffix' => ['20k'],
            'negative' => ['-5'],
            'non numeric' => ['abc'],
        ];
    }

    public function testNegativeIntegerCapIsInvalid(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with("Notifications: APP_SMS_ANNUAL_CAP invalid: '-5' — no SMS will be sent");
        $allowance = new SmsAllowance($this->connection, $logger, -5);

        self::assertSame(0, $allowance->effectiveCap());
        self::assertFalse($allowance->tryConsume(self::at(self::NOW)));
        self::assertNull($this->sent(2026));
    }

    public function testRemainingIsCapMinusSentThisFinancialYear(): void
    {
        $allowance = $this->allowance(5);
        self::assertSame(5, $allowance->remaining(self::at(self::NOW)));
        $allowance->tryConsume(self::at(self::NOW));
        $allowance->tryConsume(self::at(self::NOW));
        self::assertSame(3, $allowance->remaining(self::at(self::NOW)));
        self::assertSame(5, $allowance->remaining(self::at('2027-04-01 00:00:00 Europe/London')), 'a new financial year starts full');
    }

    public function testRemainingWithAnOverrideCapNeverGoesNegative(): void
    {
        $allowance = $this->allowance(5);
        $allowance->tryConsume(self::at(self::NOW));
        $allowance->tryConsume(self::at(self::NOW));
        self::assertSame(8, $allowance->remaining(self::at(self::NOW), 10));
        self::assertSame(0, $allowance->remaining(self::at(self::NOW), 1));
    }

    public function testInvalidCapHasNothingRemaining(): void
    {
        $allowance = new SmsAllowance($this->connection, new NullLogger(), '20k');
        self::assertSame(0, $allowance->effectiveCap());
        self::assertSame(0, $allowance->remaining(self::at(self::NOW)));
    }

    public function testIsConfigValid(): void
    {
        self::assertTrue($this->allowance(5)->isConfigValid());
        self::assertFalse($this->allowance('20k')->isConfigValid());
        self::assertTrue($this->allowance(0)->isConfigValid(), 'a configured 0 is a valid value, just an emergency stop');
    }
}
