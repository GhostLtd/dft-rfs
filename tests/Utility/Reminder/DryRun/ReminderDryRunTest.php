<?php

namespace App\Tests\Utility\Reminder\DryRun;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Tests\DataFixtures\Notification\InProgressReminderScenarioFixtures;
use App\Tests\DataFixtures\Notification\ReminderScenarioFixtures;
use App\Utility\DatabaseDescription;
use App\Utility\Notification\NotificationSender;
use App\Utility\Notification\SmsAllowance;
use App\Utility\Notification\SmsRecipientDailyCap;
use App\Utility\Reminder\DisabledRemindersHelper;
use App\Utility\Reminder\DomesticInProgressReminders;
use App\Utility\Reminder\DomesticReminders;
use App\Utility\Reminder\DryRun\DryRunOptions;
use App\Utility\Reminder\DryRun\ReminderDryRun;
use App\Utility\Reminder\RoRoReminders;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

class ReminderDryRunTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ReferenceRepository $refs;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch'); // loading these fixtures dispatches nothing; nor must the dry run
        $container->set('app.test.message_bus', $bus);
        $clock = new MockClock(InProgressReminderScenarioFixtures::NOW, 'Europe/London');
        $container->set('clock', $clock);
        $this->now = $clock->now();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->refs = $container->get(DatabaseToolCollection::class)->get()
            ->loadFixtures([ReminderScenarioFixtures::class, InProgressReminderScenarioFixtures::class])->getReferenceRepository();
    }

    private function dryRun(): ReminderDryRun
    {
        $c = static::getContainer();
        return new ReminderDryRun(
            $this->entityManager->getConnection(),
            $c->get(NotificationSender::class),
            $c->get(SmsAllowance::class),
            $c->get(SmsRecipientDailyCap::class),
            new DisabledRemindersHelper(''),
            new DatabaseDescription($this->entityManager->getConnection()),
        );
    }

    /** @return array{0: int, 1: string} exit code, output */
    private function runDry(array $families, DryRunOptions $options): array
    {
        $output = new BufferedOutput();
        $code = $this->dryRun()->run($families, $options, new SymfonyStyle(new ArrayInput([]), $output));
        return [$code, $output->fetch()];
    }

    private function options(?string $from = InProgressReminderScenarioFixtures::FROM, ?array $disabled = null, ?int $smsCap = null, ?int $smsDailyCap = null, ?int $limit = null, bool $summary = false): DryRunOptions
    {
        return new DryRunOptions($this->now, $from, $disabled, $smsCap, $smsDailyCap, $limit, $summary);
    }

    private function inProgress(): DomesticInProgressReminders
    {
        return static::getContainer()->get(DomesticInProgressReminders::class);
    }

    private function counts(): array
    {
        $c = $this->entityManager->getConnection();
        return array_map(fn(string $t) => (int) $c->fetchOne("SELECT COUNT(*) FROM {$t}"), [
            'domestic_survey_notification', 'messenger_messages', 'sms_usage', 'sms_attempt', 'notify_api_response', 'sms_recipient_day',
        ]);
    }

    public function testDryRunChangesNothingAndDispatchesNothing(): void
    {
        $before = $this->counts();
        [$code] = $this->runDry([static::getContainer()->get(DomesticReminders::class), $this->inProgress()], $this->options());
        self::assertSame(Command::SUCCESS, $code);
        self::assertSame($before, $this->counts());
    }

    public function testListsDueCountsChannelsAndHeldBack(): void
    {
        [, $out] = $this->runDry([$this->inProgress()], $this->options());

        // ip1:chased-long-ago, ip1:due, ip1:no-contact, ip1:period-end-on-from, ip1:period-end-tolerance, ip1:unchased-note-recent,
        // and ReminderScenarioFixtures' r1:wrong-state (in progress, so not a not-started reminder, but due this one).
        self::assertStringContainsString('in-progress-reminder-1: 7 due', $out);
        self::assertStringContainsString('held back (contacted in last 7 days): 4', $out);
        self::assertStringContainsString('sms 07700900001', $out, 'ip1:due has a mobile; shown unmasked');
        self::assertStringContainsString('Effective settings: APP_IN_PROGRESS_REMINDERS_FROM='.InProgressReminderScenarioFixtures::FROM, $out);
        self::assertStringContainsString('APP_SMS_DAILY_CAP_PER_NUMBER=5', $out);
    }

    public function testSmsBudgetIsSimulatedAcrossTheRun(): void
    {
        foreach (['ip1:period-end-tolerance', 'ip1:period-end-on-from'] as $i => $ref) {
            $survey = $this->refs->getReference($ref, Survey::class);
            $survey->setResponse((new SurveyResponse())->setContactTelephone('07700 90050'.$i));
        }
        $this->entityManager->flush();

        [, $out] = $this->runDry([$this->inProgress()], $this->options(smsCap: 1));

        // Other due surveys have no telephone; those skips are counted too, under their own reason.
        self::assertMatchesRegularExpression('/^SMS: 1 would send, \\d+ skipped \\(SMS allowance exhausted: 2(, |\\))/m', $out);
        self::assertStringContainsString('allowance FY2026: 0 used, 1 cap → 0 left after this run', $out);
    }

    public function testLimitChangesRowsShownButNotTotals(): void
    {
        [, $full] = $this->runDry([$this->inProgress()], $this->options());
        [, $limited] = $this->runDry([$this->inProgress()], $this->options(limit: 1));

        preg_match('/in-progress-reminder-1: (\d+) due/', $full, $a);
        preg_match('/in-progress-reminder-1: (\d+) due/', $limited, $b);
        self::assertSame($a[1], $b[1]);
        self::assertStringContainsString('more; --limit / drop --summary to list', $limited);
        preg_match('/^SMS: .*$/m', $full, $smsFull);
        preg_match('/^SMS: .*$/m', $limited, $smsLimited);
        self::assertSame($smsFull, $smsLimited);
    }

    public function testDisabledFamilyIsListedAsDisabled(): void
    {
        [, $out] = $this->runDry([static::getContainer()->get(DomesticReminders::class)], $this->options(disabled: ['csrgt']));
        self::assertStringContainsString("csrgt\n  disabled (APP_DISABLE_REMINDERS)", str_replace("\r", '', $out));
    }

    public function testInProgressDryRunWithFromUnsetReportsNote(): void
    {
        [$code, $out] = $this->runDry([$this->inProgress()], $this->options(from: null));
        self::assertSame(Command::SUCCESS, $code);
        self::assertStringContainsString("nothing sent: APP_IN_PROGRESS_REMINDERS_FROM unset or invalid", $out);
        self::assertStringContainsString('in-progress-reminder-1: 0 due', $out);
    }

    public function testCommandsAcceptDryRunOptions(): void
    {
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application(static::$kernel);
        $tester = new \Symfony\Component\Console\Tester\CommandTester($application->find('app:cron:reminders:process-in-progress'));
        self::assertSame(0, $tester->execute(['--dry-run' => true, '--from' => InProgressReminderScenarioFixtures::FROM, '--summary' => true]));
        self::assertStringContainsString('Effective settings', $tester->getDisplay());

        self::assertSame(Command::INVALID, $tester->execute(['--summary' => true]), 'dry-run options need --dry-run');

        self::assertSame(Command::INVALID, $tester->execute(['--dry-run' => true, '--sms-daily-cap' => 'five']));
        self::assertStringContainsString('--sms-daily-cap must be a whole number', $tester->getDisplay());
        self::assertSame(Command::INVALID, $tester->execute(['--sms-daily-cap' => '3']), '--sms-daily-cap needs --dry-run');
    }

    public function testPerNumberBudgetIsSeededFromTodaysCounts(): void
    {
        $dailyCap = static::getContainer()->get(SmsRecipientDailyCap::class);
        foreach (range(1, $dailyCap->effectiveCap()) as $_) {
            self::assertTrue($dailyCap->tryConsume('07700900001', $this->now));
        }
        $before = $this->counts();

        [, $out] = $this->runDry([$this->inProgress()], $this->options());

        self::assertMatchesRegularExpression('/^SMS: 0 would send, \d+ skipped \(.*SMS per-number daily cap reached: 1/m', $out);
        self::assertStringContainsString('sms skipped: SMS per-number daily cap reached', $out);
        self::assertSame($before, $this->counts(), 'the dry run consumed nothing');
    }

    public function testPerNumberBudgetIsSimulatedAcrossTheRun(): void
    {
        $survey = $this->refs->getReference('ip1:period-end-tolerance', Survey::class);
        $survey->setResponse((new SurveyResponse())->setContactTelephone('+44 7700 900001')); // ip1:due's number
        $this->entityManager->flush();

        [, $out] = $this->runDry([$this->inProgress()], $this->options(smsDailyCap: 1));

        self::assertMatchesRegularExpression('/^SMS: 1 would send, \d+ skipped \(.*SMS per-number daily cap reached: 1/m', $out);
    }

    public function testZeroDailyCapOverrideSkipsEverySmsAndLeavesTheAnnualBudget(): void
    {
        $cap = static::getContainer()->get(SmsAllowance::class)->effectiveCap();

        [, $out] = $this->runDry([$this->inProgress()], $this->options(smsDailyCap: 0));

        self::assertStringContainsString('APP_SMS_DAILY_CAP_PER_NUMBER=0', $out);
        self::assertMatchesRegularExpression('/^SMS: 0 would send, \d+ skipped \(.*SMS per-number daily cap reached: 1/m', $out);
        self::assertStringContainsString("allowance FY2026: 0 used, {$cap} cap → {$cap} left after this run", $out);
    }
}
