<?php

namespace App\Tests\Command;

use App\Command\CheckLegacyNotifyMessagesCommand;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyNotification as DomesticSurveyNotification;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRoUser;
use App\Messenger\AlphagovNotify\RoRoLoginEmail;
use Doctrine\DBAL\Connection;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Yaml\Yaml;

class CheckLegacyNotifyMessagesCommandTest extends KernelTestCase
{
    private const string NOW = '2026-10-28 12:00:00';

    /** symfony/framework-bundle's retry_strategy defaults (DependencyInjection/Configuration.php) */
    private const array FRAMEWORK_RETRY_DEFAULTS = ['max_retries' => 3, 'delay' => 1000, 'multiplier' => 2, 'max_delay' => 0, 'jitter' => 0.1];

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $this->connection->executeStatement('DELETE FROM messenger_messages');
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('DELETE FROM messenger_messages');
        parent::tearDown();
    }

    public function testAnEmptyTableIsSafe(): void
    {
        $tester = $this->check();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Safe to deploy', $tester->getDisplay());
    }

    public function testADelayedLegacyInvitationIsNotSafe(): void
    {
        $this->insert('govuk-notify', $this->body($this->email(InternationalSurvey::class)), availableAt: '+4 hours');

        $tester = $this->check();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Outstanding: 1 legacy, 0 current, 0 unrecognised, 0 unreadable', $tester->getDisplay());
        self::assertStringContainsString('1 delayed', $tester->getDisplay());
        self::assertStringContainsString('Legacy queue clear time, best case: 2026-10-28 16:00:00 UTC (when the last becomes eligible for delivery', $tester->getDisplay());
        // 4h delay, then the longest waits before each of the 3 retries: 33 + 66 + 120 minutes
        self::assertStringContainsString('Legacy queue clear time, worst case (approx.): 2026-10-28 19:39:00 UTC', $tester->getDisplay());
        self::assertStringContainsString('NOT safe to deploy', $tester->getDisplay());
    }

    public function testAnEligibleRetryNotYetPickedUpIsEstimatedFromNow(): void
    {
        $this->insert('govuk-notify', $this->body($this->email(DomesticSurvey::class), retries: 2), availableAt: '-1 hour');

        $display = $this->check()->getDisplay();
        self::assertStringContainsString('Legacy queue clear time, best case: now (all eligible for delivery since 2026-10-28 11:00:00 UTC', $display);
        self::assertStringContainsString('Legacy queue clear time, worst case (approx.): 2026-10-28 14:00:00 UTC', $display);
    }

    public function testAMessageWithNoRetriesLeftClearsOnItsNextAttempt(): void
    {
        $this->insert('govuk-notify', $this->body($this->email(DomesticSurvey::class), retries: 3), availableAt: '+10 minutes');

        self::assertStringContainsString('Legacy queue clear time, worst case (approx.): 2026-10-28 12:10:00 UTC', $this->check()->getDisplay());
    }

    public function testTheLatestClearTimeIsReported(): void
    {
        $this->insert('govuk-notify', $this->body($this->email(DomesticSurvey::class)));
        $this->insert('govuk-notify', $this->body($this->email(DomesticSurvey::class), retries: 2), availableAt: '+4 hours');

        self::assertStringContainsString('Legacy queue clear time, worst case (approx.): 2026-10-28 18:00:00 UTC', $this->check()->getDisplay());
    }

    public function testEachQueueUsesItsOwnRetryPolicy(): void
    {
        // The default queue has Symfony's default policy: waits of up to 1.1 + 2.2 + 4.4 seconds
        $this->insert('default', $this->body($this->email(DomesticSurvey::class)));

        self::assertStringContainsString('Legacy queue clear time, worst case (approx.): 2026-10-28 12:00:08 UTC', $this->check()->getDisplay());
    }

    public function testAQueueWithoutAKnownRetryPolicyGetsNoEstimate(): void
    {
        $this->insert('some-other-queue', $this->body($this->email(DomesticSurvey::class)));

        $display = $this->check()->getDisplay();
        self::assertStringNotContainsString('worst case (approx.)', $display);
        self::assertStringContainsString('Legacy queue clear time, worst case: no estimate (no known retry policy for queue(s) some-other-queue)', $display);
    }

    public function testThereIsNoClearTimeWithoutOutstandingLegacyMessages(): void
    {
        $this->insert('failed', $this->body($this->email(DomesticSurvey::class)));
        $this->insert('govuk-notify', $this->body($this->email(DomesticSurveyNotification::class)));

        self::assertStringNotContainsString('clear time', $this->check()->getDisplay());
    }

    public function testTheRetryPoliciesMatchTheMessengerConfig(): void
    {
        $transports = Yaml::parseFile(static::getContainer()->getParameter('kernel.project_dir') . '/config/packages/messenger.yaml')['framework']['messenger']['transports'];

        $checked = [];
        foreach ($transports as $name => $transport) {
            $dsn = is_array($transport) ? $transport['dsn'] : $transport;
            parse_str(parse_url($dsn, PHP_URL_QUERY) ?? '', $query);
            $queue = $query['queue_name'] ?? 'default';

            if (!str_starts_with($dsn, 'doctrine://') || !array_key_exists($queue, CheckLegacyNotifyMessagesCommand::RETRY_POLICIES)) {
                continue;
            }

            $configured = is_array($transport) ? ($transport['retry_strategy'] ?? null) : null;
            self::assertEquals($configured ?? self::FRAMEWORK_RETRY_DEFAULTS, CheckLegacyNotifyMessagesCommand::RETRY_POLICIES[$queue], "retry policy for {$name} ({$queue})");
            $checked[] = $queue;
        }

        sort($checked);
        self::assertSame(['default', 'govuk-notify', 'govuk-notify-high-prio'], $checked);
    }

    public static function legacyOrigins(): array
    {
        return [[DomesticSurvey::class], [InternationalSurvey::class], [PreEnquiry::class]];
    }

    #[DataProvider('legacyOrigins')]
    public function testEachLegacyOriginIsNotSafe(string $origin): void
    {
        $this->insert('govuk-notify', $this->body($this->email($origin)));

        $tester = $this->check();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString($origin, $tester->getDisplay());
    }

    public function testTheHighPriorityQueueIsScanned(): void
    {
        $this->insert('govuk-notify-high-prio', $this->body($this->email(DomesticSurvey::class)));

        self::assertSame(Command::FAILURE, $this->check()->getStatusCode());
    }

    public function testCurrentOriginsAreSafe(): void
    {
        $this->insert('govuk-notify', $this->body($this->email(DomesticSurveyNotification::class)));
        $this->insert('govuk-notify', $this->body($this->email(RoRoUser::class)));
        $this->insert('govuk-notify-high-prio', $this->body(new RoRoLoginEmail('someone@example.com', ['login_link' => 'x'])));

        $tester = $this->check();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Outstanding: 0 legacy, 3 current, 0 unrecognised, 0 unreadable', $tester->getDisplay());
    }

    public function testFailedMessagesAreReportedButNeverBlock(): void
    {
        $this->insert('failed', $this->body($this->email(DomesticSurvey::class)));
        $this->insert('failed', 'x');
        $this->insert('govuk-notify', $this->body($this->email(DomesticSurveyNotification::class)));

        $tester = $this->check();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Outstanding: 0 legacy, 1 current, 0 unrecognised, 0 unreadable', $tester->getDisplay());
        self::assertStringContainsString('Failed: 1 legacy, 0 current, 0 unrecognised, 1 unreadable', $tester->getDisplay());
    }

    public function testAnUnreadableOutstandingMessageIsNotSafeAndIsLeftInPlace(): void
    {
        $id = $this->insert('govuk-notify', 'x');

        $tester = $this->check();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('0 unrecognised, 1 unreadable', $tester->getDisplay());
        self::assertStringContainsString((string) $id, $tester->getDisplay());
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages WHERE id = ?', [$id]), 'the row must not be deleted');
    }

    public function testATruncatedEnvelopeThatKeepsItsClassOriginAndClosingBraceIsUnreadable(): void
    {
        $serialized = serialize(new Envelope($this->email(DomesticSurveyNotification::class)));
        $cut = strpos($serialized, 'originatingEntityId') + 40;
        $truncated = substr($serialized, 0, $cut) . '}';
        self::assertStringContainsString(DomesticSurveyNotification::class, $truncated);

        $this->insert('govuk-notify', addslashes($truncated));

        $tester = $this->check();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('0 current, 0 unrecognised, 1 unreadable', $tester->getDisplay());
    }

    public function testTrailingJunkAfterACurrentMessageIsUnreadable(): void
    {
        // PHP returns the object with only a warning ("Extra data starting at offset …"); Symfony rejects it
        $this->insert('govuk-notify', addslashes(serialize(new Envelope($this->email(DomesticSurveyNotification::class))) . ' junk}'));

        $tester = $this->check();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('0 current, 0 unrecognised, 1 unreadable', $tester->getDisplay());
    }

    public function testTheErrorHandlerIsRestoredAfterAnUnreadableMessage(): void
    {
        $this->insert('govuk-notify', addslashes(serialize(new Envelope($this->email(DomesticSurveyNotification::class))) . ' junk}'));

        $sentinel = fn() => false;
        set_error_handler($sentinel);
        try {
            $this->check();

            $current = set_error_handler(fn() => false);
            restore_error_handler();
        } finally {
            restore_error_handler();
        }

        self::assertSame($sentinel, $current);
    }

    public function testUnrecognisedOriginsAndMessagesAreNotSafe(): void
    {
        $this->insert('govuk-notify', $this->body($this->email(null)));
        $this->insert('govuk-notify', $this->body($this->email('App\Entity\Something\Else')));
        $this->insert('default', $this->body(new \stdClass()));

        $tester = $this->check();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Outstanding: 0 legacy, 0 current, 3 unrecognised, 0 unreadable', $tester->getDisplay());
    }

    public function testPreBundleMessageClassesAreRecognisedAsLegacy(): void
    {
        $this->insert('failed', $this->preBundleBody());

        $tester = $this->check();
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Failed: 1 legacy, 0 current, 0 unrecognised, 0 unreadable', $tester->getDisplay());
        self::assertStringContainsString('App\Messenger\AlphagovNotify\Email', $tester->getDisplay());

        $this->insert('govuk-notify', $this->preBundleBody());

        $tester = $this->check();
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Outstanding: 1 legacy, 0 current, 0 unrecognised, 0 unreadable', $tester->getDisplay());
    }

    public function testABase64EncodedBodyIsDecoded(): void
    {
        $body = $this->body($this->email(DomesticSurvey::class, ['name' => "\xff"]));
        self::assertStringEndsNotWith('}', $body, 'PhpSerializer base64-encodes a body that is not valid UTF-8');

        $this->insert('govuk-notify', $body);

        self::assertStringContainsString('Outstanding: 1 legacy', $this->check()->getDisplay());
    }

    public function testAMessageMarkedDeliveredIsStillOutstanding(): void
    {
        $this->insert('govuk-notify', $this->body($this->email(DomesticSurvey::class)), deliveredAt: '-1 hour');

        $tester = $this->check();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('1 marked delivered', $tester->getDisplay());
    }

    public function testEveryRowIsCountedOnceAcrossPages(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->insert('govuk-notify', $this->body($this->email(DomesticSurveyNotification::class)));
        }

        self::assertStringContainsString('Outstanding: 0 legacy, 5 current', $this->check(pageSize: 2)->getDisplay());
    }

    private function check(int $pageSize = 200): CommandTester
    {
        $clock = new MockClock(self::NOW, 'UTC');
        $tester = new CommandTester(new CheckLegacyNotifyMessagesCommand($this->connection, $clock, $pageSize));
        $tester->execute([]);
        return $tester;
    }

    private function email(?string $origin, array $personalisation = []): Email
    {
        return new Email('invite', $origin, $origin === null ? null : 'b3ad7e9c-4b4a-4d5e-9a40-0d6f5a0b8a11', 'someone@example.com', 'template-id', $personalisation);
    }

    private function body(object $message, int $retries = 0): string
    {
        $stamps = $retries > 0 ? [new RedeliveryStamp($retries)] : [];
        return (new PhpSerializer())->encode(new Envelope($message, $stamps))['body'];
    }

    /** A message class from before the 2026-05 bundle migration, which no longer exists */
    private function preBundleBody(): string
    {
        $from = Email::class;
        $to = 'App\Messenger\AlphagovNotify\Email';
        $serialized = str_replace(
            sprintf('O:%d:"%s"', strlen($from), $from),
            sprintf('O:%d:"%s"', strlen($to), $to),
            serialize(new Envelope($this->email(DomesticSurvey::class)))
        );

        return addslashes($serialized);
    }

    /** Times are relative to the fixed NOW, in UTC (as the Doctrine transport writes them) */
    private function insert(string $queue, string $body, string $availableAt = 'now', ?string $deliveredAt = null): int
    {
        $utc = fn(string $time) => (new \DateTimeImmutable(self::NOW, new \DateTimeZone('UTC')))->modify($time)->format('Y-m-d H:i:s');

        $this->connection->insert('messenger_messages', [
            'body' => $body,
            'headers' => '[]',
            'queue_name' => $queue,
            'created_at' => $utc('now'),
            'available_at' => $utc($availableAt),
            'delivered_at' => $deliveredAt === null ? null : $utc($deliveredAt),
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
