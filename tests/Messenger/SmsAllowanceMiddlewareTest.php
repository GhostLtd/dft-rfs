<?php

namespace App\Tests\Messenger;

use Alphagov\Notifications\Exception\ApiException;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Messenger\SmsAllowanceMiddleware;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use App\Utility\Notification\SmsAllowance;
use App\Utility\Notification\SmsRecipientDailyCap;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Worker;

class SmsAllowanceMiddlewareTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private TransportInterface $notify;
    private TransportInterface $failed;
    private MockClock $clock;
    private const string SECRET = 'test-secret';
    private const string MOBILE = '07700900001';
    private int $handled = 0;
    /** @var list<\Throwable> thrown by the stub Notify handler, in order; once empty it succeeds */
    private array $handlerFailures = [];
    /** @var list<bool> whether each received envelope carried a RedeliveryStamp */
    private array $redelivered = [];

    protected function setUp(): void
    {
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->connection = $this->entityManager->getConnection();
        $this->notify = $container->get('messenger.transport.async_notify');
        $this->failed = $container->get('messenger.transport.failed');
        $this->clock = new MockClock('2026-09-10 09:00:00', 'Europe/London');
    }

    private function allowance(int|string|null $cap, ?LoggerInterface $logger = null): SmsAllowance
    {
        return new SmsAllowance($this->connection, $logger ?? new NullLogger(), $cap);
    }

    private function dailyCap(int|string|null $cap, ?LoggerInterface $logger = null): SmsRecipientDailyCap
    {
        return new SmsRecipientDailyCap($this->connection, $logger ?? new NullLogger(), $cap, self::SECRET);
    }

    /** Queues an SMS as NotificationSender would at $queuedAt: row, per-number and allowance units, ledger reservation, message. */
    private function queueSms(string $queuedAt, int $cap, int $perNumberCap = 5): SurveyNotification
    {
        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2026-08-01'))->setSurveyPeriodEnd(new \DateTime('2026-08-07'))
            ->setIsNorthernIreland(false)->setRegistrationMark('AB12CDE')
            ->setPasscodeUser((new PasscodeUser())->setUsername('sms-test')->setPlainPassword('x'))
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS);
        $row = (new SurveyNotification())->setType(NotificationType::IN_PROGRESS_REMINDER_1)->setChannel(NotificationChannel::SMS)->setSentAt(new \DateTime($queuedAt));
        $survey->addNotification($row);
        $this->entityManager->persist($survey);
        $this->entityManager->persist($row);
        $this->entityManager->flush();
        $this->connection->executeStatement('DELETE FROM messenger_messages'); // anything the survey's own listeners queued

        $at = new \DateTimeImmutable($queuedAt, new \DateTimeZone('Europe/London'));
        self::assertTrue($this->dailyCap($perNumberCap)->tryConsume(self::MOBILE, $at));
        self::assertTrue($this->allowance($cap)->tryConsume($at));
        $this->allowance($cap)->recordReservation($row->getId(), $at);
        $this->notify->send(new Envelope(new Sms('in-progress-reminder-1', SurveyNotification::class, $row->getId(), self::MOBILE, 'template-id')));

        return $row;
    }

    private function work(int|string|null $cap, int|string|null $perNumberCap = 5, ?LoggerInterface $logger = null): void
    {
        $logger ??= new NullLogger();
        $bus = new MessageBus([
            new SmsAllowanceMiddleware($this->allowance($cap, $logger), $this->dailyCap($perNumberCap, $logger), $this->clock, $logger),
            new HandleMessageMiddleware(new HandlersLocator([Sms::class => [function (Sms $sms): array {
                $this->handled++;
                if ($failure = array_shift($this->handlerFailures)) {
                    throw $failure;
                }
                return ['id' => 'notify-'.$this->handled];
            }]])),
        ]);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new SendFailedMessageForRetryListener(
            new ServiceLocator(['async_notify' => fn() => $this->notify]),
            new ServiceLocator(['async_notify' => fn() => new MultiplierRetryStrategy(3, 1800000, 2, 7200000)]),
        ));
        $dispatcher->addSubscriber(new SendFailedMessageToFailureTransportListener(new ServiceLocator(['async_notify' => fn() => $this->failed])));
        $dispatcher->addSubscriber(static::getContainer()->get(AlphagovNotifyMessengerSubscriber::class));
        $dispatcher->addListener(WorkerMessageReceivedEvent::class, function (WorkerMessageReceivedEvent $e): void {
            $this->redelivered[] = $e->getEnvelope()->last(RedeliveryStamp::class) !== null;
        });
        $dispatcher->addListener(WorkerRunningEvent::class, function (WorkerRunningEvent $e): void {
            if ($e->isWorkerIdle()) {
                $e->getWorker()->stop();
            }
        });

        (new Worker(['async_notify' => $this->notify], $bus, $dispatcher))->run(['sleep' => 0]);
    }

    /** Messenger retries are delayed 30+ minutes; make them available now. */
    private function releaseDelayedRetries(): void
    {
        $this->connection->executeStatement("UPDATE messenger_messages SET available_at = '2000-01-01 00:00:00' WHERE queue_name = 'govuk-notify'");
    }

    private function queued(string $queue): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages WHERE queue_name = ?', [$queue]);
    }

    private function sent(int $financialYear): ?int
    {
        $sent = $this->connection->fetchOne('SELECT sent FROM sms_usage WHERE financial_year = ?', [$financialYear]);
        return $sent === false ? null : (int) $sent;
    }

    private function perNumberSent(string $day): ?int
    {
        $sent = $this->connection->fetchOne('SELECT sent FROM sms_recipient_day WHERE recipient_hmac = ? AND day = ?', [hash_hmac('sha256', self::MOBILE, self::SECRET), $day]);
        return $sent === false ? null : (int) $sent;
    }

    private function attempts(string $notificationId): ?int
    {
        $attempts = $this->connection->fetchOne('SELECT attempts FROM sms_attempt WHERE notification_id = ?', [$notificationId]);
        return $attempts === false ? null : (int) $attempts;
    }

    /** @return list<string> the error text of the row's failed responses that came from a refusal (not a handler failure) */
    private function refusals(SurveyNotification $row): array
    {
        $this->entityManager->clear();
        $responses = $this->entityManager->find(SurveyNotification::class, $row->getId())->getNotifyApiResponses()->toArray();
        $errors = array_map(fn($r) => (string) ($r->getData()['errors'][0]['error'] ?? ''), $responses);

        return array_values(array_filter($errors, fn(string $e) => str_contains($e, 'SMS ')));
    }

    public function testFirstAttemptIsAlreadyPaidFor(): void
    {
        $row = $this->queueSms('2026-09-10 09:00:00', 5);
        $this->work(5);

        self::assertSame(1, $this->handled);
        self::assertSame(1, $this->sent(2026));
        self::assertSame(1, $this->perNumberSent('2026-09-10'), 'paid for at queue time');
        self::assertSame(0, $this->queued('govuk-notify'));

        $this->entityManager->clear();
        $responses = $this->entityManager->find(SurveyNotification::class, $row->getId())->getNotifyApiResponses()->toArray();
        self::assertCount(1, $responses);
        self::assertSame(Reference::STATUS_ACCEPTED, $responses[0]->getData()[AlphagovNotifyMessengerSubscriber::STATUS_KEY]);
    }

    public function testMessengerRetryConsumesAnotherUnit(): void
    {
        $this->handlerFailures = [new \RuntimeException('timeout: response lost')];
        $this->queueSms('2026-09-10 09:00:00', 5);
        $this->work(5);
        self::assertSame(1, $this->queued('govuk-notify'), 'scheduled for retry');

        $this->releaseDelayedRetries();
        $this->work(5);

        self::assertSame(2, $this->handled);
        self::assertSame([false, true], $this->redelivered);
        self::assertSame(2, $this->sent(2026));
        self::assertSame(2, $this->perNumberSent('2026-09-10'));
    }

    public function testCrashRedeliveryWithoutRedeliveryStampConsumesAnotherUnit(): void
    {
        $row = $this->queueSms('2026-09-10 09:00:00', 5);

        // A worker took the message, the middleware counted the attempt, then the worker died before acking.
        iterator_to_array($this->notify->get());
        $this->allowance(5)->recordAttempt($row->getId(), $this->clock->now());
        $this->connection->executeStatement("UPDATE messenger_messages SET delivered_at = '2000-01-01 00:00:00' WHERE queue_name = 'govuk-notify'");

        $this->work(5);

        self::assertSame(1, $this->handled);
        self::assertSame([false], $this->redelivered, 'Doctrine redelivery carries no RedeliveryStamp');
        self::assertSame(2, $this->sent(2026), 'the redelivered attempt was charged');
        self::assertSame(2, $this->perNumberSent('2026-09-10'), 'and to the number');
    }

    public function testExhaustedAllowanceFailsTheRepeatTerminally(): void
    {
        $this->handlerFailures = [new \RuntimeException('timeout: response lost')];
        $row = $this->queueSms('2026-09-10 09:00:00', 1);
        $this->work(1);
        $this->releaseDelayedRetries();
        $this->work(1);

        self::assertSame(1, $this->handled, 'the refused repeat never reached Notify');
        self::assertSame(0, $this->queued('govuk-notify'), 'acked, not left to come back after the redeliver timeout');
        self::assertSame(1, $this->queued('failed'));

        // The first (handler) failure is recorded too, so pick out the refusal by its error text.
        $this->entityManager->clear();
        $responses = $this->entityManager->find(SurveyNotification::class, $row->getId())->getNotifyApiResponses()->toArray();
        $refusals = array_values(array_filter($responses, fn($r) => str_contains($r->getData()['errors'][0]['error'] ?? '', 'SMS allowance')));
        self::assertCount(1, $refusals);
        self::assertSame(Reference::STATUS_REFUSED, $refusals[0]->getData()[AlphagovNotifyMessengerSubscriber::STATUS_KEY]);
        self::assertSame(2, $this->perNumberSent('2026-09-10'), 'the per-number unit taken before the annual refusal is not given back');
    }

    #[DataProvider('refusalMessages')]
    /**
     * Refused by our own caps, not by Notify: says nothing about the number, so it's "refused", not a failure (and
     * so not a delivery problem)
     */
    public function testRefusalWithNoPreviousExceptionIsRecordedAsARefusedResponse(int $annual, int $perNumber, string $error): void
    {
        $row = $this->queueSms('2026-09-10 09:00:00', 5);
        $this->work($annual, $perNumber);

        $this->entityManager->clear();
        $responses = $this->entityManager->find(SurveyNotification::class, $row->getId())->getNotifyApiResponses()->toArray();
        self::assertCount(1, $responses);
        $data = $responses[0]->getData();
        self::assertSame(Reference::STATUS_REFUSED, $data[AlphagovNotifyMessengerSubscriber::STATUS_KEY]);
        self::assertSame($error, $data['errors'][0]['error'], 'the refusal itself is logged when it has no previous exception');
    }

    public static function refusalMessages(): array
    {
        return [
            'annual' => [0, 5, 'SMS allowance for FY 2026 exhausted'],
            'per-number' => [5, 0, 'SMS per-number daily cap reached'],
        ];
    }

    #[DataProvider('notifyApiFailures')]
    public function testWrappedNotifyApiFailureIsRecordedFromTheApiException(bool $unrecoverable, int $code, string $status): void
    {
        $errors = [['error' => 'BadRequestError', 'message' => 'Can\'t send to this recipient']];
        $apiException = new ApiException('', $code, ['errors' => $errors], $this->createStub(ResponseInterface::class));
        // As the bundle's MessageHandler throws them: 400/403 wrapped as unrecoverable, anything else bare.
        $this->handlerFailures = [$unrecoverable ? new UnrecoverableMessageHandlingException('', 0, $apiException) : $apiException];
        $row = $this->queueSms('2026-09-10 09:00:00', 5);
        $this->work(5);

        $this->entityManager->clear();
        $responses = $this->entityManager->find(SurveyNotification::class, $row->getId())->getNotifyApiResponses()->toArray();
        self::assertCount(1, $responses);
        $data = $responses[0]->getData();
        self::assertSame($code, $data['code']);
        self::assertSame($errors, $data['errors']);
        self::assertSame($status, $data[AlphagovNotifyMessengerSubscriber::STATUS_KEY]);
    }

    public static function notifyApiFailures(): array
    {
        return [
            'rejected, unrecoverable' => [true, 400, Reference::STATUS_FAILED],
            'server error, retried' => [false, 500, Reference::STATUS_WAITING_FOR_RETRY],
        ];
    }

    public function testFirstAttemptAcrossTheYearBoundaryConsumesTheNewYear(): void
    {
        $this->queueSms('2027-03-31 23:59:00', 5);
        $this->clock = new MockClock('2027-04-01 00:01:00', 'Europe/London');
        $this->work(5);

        self::assertSame(1, $this->handled);
        self::assertSame(1, $this->sent(2026));
        self::assertSame(1, $this->sent(2027));
        self::assertSame(1, $this->perNumberSent('2027-03-31'));
        self::assertSame(1, $this->perNumberSent('2027-04-01'), 'a new day too');
    }

    public function testFirstAttemptAcrossTheYearBoundaryIsRefusedWhenTheNewYearIsFull(): void
    {
        $this->queueSms('2027-03-31 23:59:00', 1);
        $this->connection->insert('sms_usage', ['financial_year' => 2027, 'sent' => 1]);
        $this->clock = new MockClock('2027-04-01 00:01:00', 'Europe/London');
        $this->work(1);

        self::assertSame(0, $this->handled);
        self::assertSame(1, $this->queued('failed'));
    }

    public function testEmailAndDispatchSideEnvelopesPassStraightThrough(): void
    {
        $allowance = $this->createMock(SmsAllowance::class);
        $allowance->expects(self::never())->method('recordAttempt');
        $dailyCap = $this->createMock(SmsRecipientDailyCap::class);
        $dailyCap->expects(self::never())->method('tryConsume');
        $middleware = new SmsAllowanceMiddleware($allowance, $dailyCap, $this->clock, new NullLogger());

        $middleware->handle(new Envelope(new Email('e', SurveyNotification::class, 'n-1', 'a@example.com', 't'), [new ReceivedStamp('async_notify')]), new StackMiddleware());
        $middleware->handle(new Envelope(new Sms('e', SurveyNotification::class, 'n-2', '07700900001', 't')), new StackMiddleware());
        $this->addToAssertionCount(1);
    }

    public function testFirstAttemptHandledTheNextDayIsChargedToTheNewDayOnly(): void
    {
        $this->queueSms('2026-09-10 23:59:00', 5);
        $this->clock = new MockClock('2026-09-11 00:01:00', 'Europe/London');
        $this->work(5);

        self::assertSame(1, $this->handled);
        self::assertSame(1, $this->perNumberSent('2026-09-10'));
        self::assertSame(1, $this->perNumberSent('2026-09-11'), 'charged to the day it was handled');
        self::assertSame(1, $this->sent(2026), 'same financial year: its annual unit was already paid');
    }

    public function testLondonDayIsUsedWhenTheWorkerClockIsUtc(): void
    {
        $this->queueSms('2026-09-10 23:30:00', 5); // London (BST)
        $this->clock = new MockClock('2026-09-10 23:10:00', 'UTC'); // 00:10 on 11 September in London
        $this->work(5);

        self::assertSame(1, $this->perNumberSent('2026-09-11'), 'a UTC clock still on the 10th is already the 11th in London');
    }

    public function testAttemptWithNoLedgerRowIsRecordedAndChargedToBoth(): void
    {
        $row = $this->queueSms('2026-09-10 09:00:00', 5);
        $this->connection->executeStatement('DELETE FROM sms_attempt');
        $this->work(5);

        self::assertSame(1, $this->handled);
        self::assertSame(2, $this->sent(2026));
        self::assertSame(2, $this->perNumberSent('2026-09-10'));
        self::assertSame(1, $this->attempts($row->getId()));
    }

    public function testPreMigrationLedgerRowIsChargedPerNumberOnly(): void
    {
        $this->queueSms('2026-09-10 09:00:00', 5);
        $this->connection->executeStatement('UPDATE sms_attempt SET reserved_on = NULL');
        $this->work(5);

        self::assertSame(1, $this->handled);
        self::assertSame(2, $this->perNumberSent('2026-09-10'), 'reserved_on unknown: charged');
        self::assertSame(1, $this->sent(2026), 'annual rule unchanged: a first attempt in the reserving year is paid');
    }

    public function testRetryIsRefusedTerminallyAtThePerNumberCap(): void
    {
        $this->handlerFailures = [new \RuntimeException('timeout: response lost')];
        $row = $this->queueSms('2026-09-10 09:00:00', 5, 1);
        $this->work(5, 1);
        $this->releaseDelayedRetries();
        $this->work(5, 1);

        self::assertSame(1, $this->handled, 'the refused repeat never reached Notify');
        self::assertSame(0, $this->queued('govuk-notify'));
        self::assertSame(1, $this->queued('failed'));
        self::assertSame(1, $this->sent(2026), 'refused on the per-number cap before the annual allowance was charged');

        $this->entityManager->clear();
        $responses = $this->entityManager->find(SurveyNotification::class, $row->getId())->getNotifyApiResponses()->toArray();
        $refusals = array_values(array_filter($responses, fn($r) => str_contains($r->getData()['errors'][0]['error'] ?? '', 'per-number')));
        self::assertCount(1, $refusals);
        self::assertSame(Reference::STATUS_REFUSED, $refusals[0]->getData()[AlphagovNotifyMessengerSubscriber::STATUS_KEY]);
    }

    #[DataProvider('stoppedCaps')]
    public function testEmergencyStopRefusesAnAlreadyPaidFirstAttemptWithoutTouchingTheLedger(int|string $annual, int|string $perNumber, string $error): void
    {
        $row = $this->queueSms('2026-09-10 09:00:00', 5); // queued while both caps were normal
        $this->work($annual, $perNumber);

        self::assertSame(0, $this->handled);
        self::assertSame(1, $this->queued('failed'));
        self::assertSame(0, $this->attempts($row->getId()), 'the refused attempt was never recorded');
        self::assertSame(1, $this->sent(2026));
        self::assertSame(1, $this->perNumberSent('2026-09-10'));
        $refusals = $this->refusals($row);
        self::assertCount(1, $refusals);
        self::assertStringContainsString($error, $refusals[0]);
    }

    public static function stoppedCaps(): array
    {
        return [
            'annual switched to 0' => [0, 5, 'SMS allowance'],
            'annual invalid' => ['20k', 5, 'SMS allowance'],
            'per-number switched to 0' => [20000, 0, 'per-number'],
            'per-number invalid' => [20000, 'five', 'per-number'],
        ];
    }

    #[DataProvider('emergencyStopLoggingCases')]
    public function testEmergencyStopLoggingMatchesTheSpec(int|string $annual, int|string $perNumber, string $expectedLevel, string $expectedContains, string $forbiddenLevel, ?string $forbiddenContains): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{0: string, 1: string}> */
            public array $logs = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logs[] = [(string) $level, (string) $message];
            }
        };
        $this->queueSms('2026-09-10 09:00:00', 5); // queued while both caps were normal
        $this->work($annual, $perNumber, $logger);

        $matches = array_values(array_filter($logger->logs, fn(array $l) => $l[0] === $expectedLevel && str_contains($l[1], $expectedContains)));
        self::assertCount(1, $matches, "expected exactly one {$expectedLevel}() containing '{$expectedContains}'");

        $forbidden = array_values(array_filter($logger->logs, fn(array $l) => $l[0] === $forbiddenLevel && ($forbiddenContains === null || str_contains($l[1], $forbiddenContains))));
        self::assertCount(0, $forbidden, "expected no {$forbiddenLevel}()".($forbiddenContains !== null ? " containing '{$forbiddenContains}'" : ''));
    }

    public static function emergencyStopLoggingCases(): array
    {
        return [
            'invalid annual logs only the error' => ['20k', 5, 'error', 'APP_SMS_ANNUAL_CAP invalid', 'warning', 'cap is 0'],
            'configured annual zero logs only the warning' => [0, 5, 'warning', 'SMS annual cap is 0', 'error', null],
            'invalid per-number logs only the error' => [5, 'five', 'error', 'APP_SMS_DAILY_CAP_PER_NUMBER invalid', 'warning', 'cap is 0'],
            'configured per-number zero logs only the warning' => [5, 0, 'warning', 'SMS per-number cap is 0', 'error', null],
        ];
    }
}
