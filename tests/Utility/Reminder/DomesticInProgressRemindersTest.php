<?php

namespace App\Tests\Utility\Reminder;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Tests\DataFixtures\Notification\InProgressReminderScenarioFixtures;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationQueryHelper;
use App\Utility\Notification\NotificationSender;
use App\Utility\Notification\NotificationType;
use App\Utility\Notification\SmsRecipientDailyCap;
use App\Utility\Reminder\DomesticInProgressReminders;
use App\Utility\Reminder\DueItem;
use App\Utility\Reminder\HeldBack;
use App\Utility\Reminder\ManualReminderHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class DomesticInProgressRemindersTest extends KernelTestCase
{
    use ResolvesFixtureRefsTrait;

    private EntityManagerInterface $entityManager;
    private ReferenceRepository $refs;
    private MockClock $clock;
    /** @var list<object> */
    private array $dispatched = [];

    protected function setUp(): void
    {
        $container = static::getContainer();

        // The bus override must land before anything resolves the real one (the fixtures below can).
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $m, array $s = []) {
            $this->dispatched[] = $m;
            return new Envelope($m, $s);
        });
        $container->set('app.test.message_bus', $bus);

        // One clock for the family and the container's NotificationSender; tests move it with modify().
        $this->clock = new MockClock(InProgressReminderScenarioFixtures::NOW, 'Europe/London');
        $container->set('clock', $this->clock);

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->refs = $container->get(DatabaseToolCollection::class)->get()->loadFixtures([InProgressReminderScenarioFixtures::class])->getReferenceRepository();
        $this->dispatched = [];
    }

    private function family(?string $from = InProgressReminderScenarioFixtures::FROM, ?LoggerInterface $logger = null): DomesticInProgressReminders
    {
        $container = static::getContainer();
        return new DomesticInProgressReminders(
            $this->entityManager,
            $container->get(NotificationQueryHelper::class),
            $container->get(NotificationSender::class),
            $container->get(ManualReminderHelper::class),
            $container->get(SmsRecipientDailyCap::class),
            $this->clock,
            $logger ?? new NullLogger(),
            $from,
        );
    }

    private function now(): \DateTimeImmutable
    {
        return $this->clock->now();
    }

    /** @return list<string> */
    private function dueRefs(NotificationType $type, ?DomesticInProgressReminders $family = null): array
    {
        return $this->refsOf(Survey::class, array_map(fn(DueItem $i) => $i->surveys[0], ($family ?? $this->family())->due($this->now())->ofType($type)));
    }

    private function reload(string $ref): Survey
    {
        $this->entityManager->clear();
        return $this->entityManager->find(Survey::class, $this->refs->getReference($ref, Survey::class)->getId());
    }

    public function testFamilyName(): void
    {
        self::assertSame('csrgt-in-progress', $this->family()->getFamily());
    }

    public function testDueForReminder1(): void
    {
        self::assertSame(
            ['ip1:chased-long-ago', 'ip1:due', 'ip1:no-contact', 'ip1:period-end-on-from', 'ip1:period-end-tolerance', 'ip1:unchased-note-recent'],
            $this->dueRefs(NotificationType::IN_PROGRESS_REMINDER_1),
        );
    }

    public function testDueForReminder2(): void
    {
        self::assertSame(['ip2:due', 'ip2:tolerance'], $this->dueRefs(NotificationType::IN_PROGRESS_REMINDER_2));
    }

    public function testDueForReminder3(): void
    {
        self::assertSame(['ip3:due'], $this->dueRefs(NotificationType::IN_PROGRESS_REMINDER_3));
    }

    public function testRecentContactStopsDeferringAfterSevenDays(): void
    {
        // A weekday, as due() selects nothing at weekends: chased 2026-09-05 10:00 is now more than 7 days ago.
        $this->clock->modify('2026-09-14 09:00:00');
        $due = $this->dueRefs(NotificationType::IN_PROGRESS_REMINDER_1);
        self::assertContains('ip1:chased-recently', $due);
        self::assertNotContains('ip1:started-recently', $due, 'moved to in-progress 2026-09-07 10:00: still within 7 days');
    }

    public function testRunSendsEmailAndSmsWithNoDelay(): void
    {
        $this->family()->run();

        $survey = $this->reload('ip1:due');
        self::assertCount(1, $survey->getNotificationsOfType(NotificationType::IN_PROGRESS_REMINDER_1, NotificationChannel::EMAIL));
        $sms = $survey->getNotificationsOfType(NotificationType::IN_PROGRESS_REMINDER_1, NotificationChannel::SMS);
        self::assertCount(1, $sms);
        // Compared as wall-clock text: DATETIME columns carry no zone, so the reloaded value is in PHP's default zone.
        self::assertSame('2026-09-10 09:00', $sms[0]->getSentAt()->format('Y-m-d H:i'), 'sentAt is the run time');
        self::assertCount(1, array_filter($this->dispatched, fn(object $m) => $m instanceof Sms), 'only ip1:due has a mobile');

        self::assertTrue($this->reload('ip2:due')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_2));
        self::assertTrue($this->reload('ip3:due')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_3));
        self::assertFalse($this->reload('ip1:chased-recently')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_1));
        self::assertFalse($this->reload('ip2:closed')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_2));
        self::assertFalse($this->reload('ip1:due')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_2), 'reminder 1 sent this run cannot be followed in the same run');

        $connection = $this->entityManager->getConnection();
        self::assertSame(1, (int) $connection->fetchOne('SELECT sent FROM sms_usage WHERE financial_year = 2026'));
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM sms_attempt WHERE attempts = 0'));
    }

    public function testNoContactRecordsNothingAndStaysDue(): void
    {
        $family = $this->family();
        $family->run();

        self::assertFalse($this->reload('ip1:no-contact')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_1));
        self::assertContains('ip1:no-contact', $this->dueRefs(NotificationType::IN_PROGRESS_REMINDER_1, $family));
    }

    #[DataProvider('weekendDays')]
    public function testWeekendSendsNothing(string $when): void
    {
        $this->clock->modify($when);
        $this->family()->run();

        self::assertSame([], $this->dispatched);
        self::assertFalse($this->reload('ip1:due')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_1));
    }

    public static function weekendDays(): array
    {
        return ['Saturday' => ['2026-09-12 09:00:00'], 'Sunday' => ['2026-09-13 09:00:00']];
    }

    public function testMondaySendsWhatFellDueAtTheWeekend(): void
    {
        $this->clock->modify('2026-09-14 09:00:00');
        $this->family()->run();

        self::assertTrue($this->reload('ip1:due')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_1));
    }

    #[DataProvider('unusableFrom')]
    public function testUnsetOrInvalidFromSendsNothingAndLogsAnError(?string $from): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with(self::stringContains('APP_IN_PROGRESS_REMINDERS_FROM'));

        $family = $this->family($from, $logger);
        $family->run();

        self::assertSame([], $this->dispatched);
        self::assertSame([], $family->due($this->now())->items);
    }

    public static function unusableFrom(): array
    {
        return ['unset' => [null], 'empty' => [''], 'not a date' => ['soon'], 'impossible date' => ['2026-02-31'], 'wrong format' => ['15/08/2026']];
    }

    public function testDueListsTheHeldBack(): void
    {
        $due = $this->family()->due($this->now());

        $held = $this->refsOf(Survey::class, array_map(fn(HeldBack $h) => $h->survey, $due->heldBack));
        self::assertSame(['ip1:chased-recently', 'ip1:manual-reminder-recently', 'ip1:started-recently', 'ip2:chased-recently'], $held);
        foreach ($due->heldBack as $h) {
            self::assertNotNull($h->latestContact);
            self::assertContains($h->event, ['was_chased', 'manual_reminder', 'in_progress']);
        }
    }

    public function testDueExplainsTheWeekendAndTheUnsetDate(): void
    {
        $this->clock->modify('2026-09-12 09:00:00');
        $weekend = $this->family()->due($this->now());
        self::assertSame([], $weekend->items);
        self::assertStringContainsString('weekend', $weekend->notes[0]);

        $unset = $this->family(null)->due($this->now());
        self::assertSame([], $unset->items);
        self::assertStringContainsString('APP_IN_PROGRESS_REMINDERS_FROM unset or invalid', $unset->notes[0]);
    }

    public function testWithRemindersFromReplacesOnlyTheDate(): void
    {
        $other = $this->family(null)->withRemindersFrom(InProgressReminderScenarioFixtures::FROM);
        self::assertSame(InProgressReminderScenarioFixtures::FROM, $other->getRemindersFromSetting());
        self::assertNotSame([], $other->due($this->now())->items);
    }

    #[DataProvider('pruneRuns')]
    public function testRunPrunesEarlierDaysFirst(string $when, ?string $from): void
    {
        $connection = $this->entityManager->getConnection();
        $this->clock->modify($when);
        $today = SmsRecipientDailyCap::day($this->now());
        $yesterday = SmsRecipientDailyCap::day($this->now()->modify('-1 day'));
        $connection->insert('sms_recipient_day', ['recipient_hmac' => str_repeat('a', 64), 'day' => $yesterday, 'sent' => 5]);
        $connection->insert('sms_recipient_day', ['recipient_hmac' => str_repeat('b', 64), 'day' => $today, 'sent' => 1]);

        $this->family($from)->run();

        self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM sms_recipient_day WHERE day < ?', [$today]));
        self::assertSame(1, (int) $connection->fetchOne('SELECT sent FROM sms_recipient_day WHERE recipient_hmac = ?', [str_repeat('b', 64)]), "today's row is kept");
    }

    public static function pruneRuns(): array
    {
        return [
            'weekday' => ['2026-09-10 09:00:00', InProgressReminderScenarioFixtures::FROM],
            'weekend: prunes before returning' => ['2026-09-12 09:00:00', InProgressReminderScenarioFixtures::FROM],
            'from unset: prunes before returning' => ['2026-09-10 09:00:00', null],
        ];
    }

    public function testSurveysSharingANumberShareItsDailyCap(): void
    {
        // ip2:due (reminder 2) gets ip1:due's (reminder 1) number, written differently.
        $ip2 = $this->refs->getReference('ip2:due', Survey::class);
        $ip2->setResponse((new SurveyResponse())->setContactTelephone('+44 7700 900001'));
        $this->entityManager->flush();
        $dailyCap = static::getContainer()->get(SmsRecipientDailyCap::class);
        foreach (range(1, $dailyCap->effectiveCap() - 1) as $_) {
            self::assertTrue($dailyCap->tryConsume('07700900001', $this->now()));
        }

        $this->family()->run();

        self::assertCount(1, array_filter($this->dispatched, fn(object $m) => $m instanceof Sms), 'one unit was left for the two surveys');
        self::assertTrue($this->reload('ip1:due')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_1));
        self::assertTrue($this->reload('ip2:due')->hasNotification(NotificationType::IN_PROGRESS_REMINDER_2), 'its email still went');
    }
}
