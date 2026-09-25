<?php

namespace App\Tests\Command\Dev;

use App\Tests\DataFixtures\Rehearsal\BaselineReferenceFixtures;
use App\Tests\Utility\Rehearsal\SimulatesNotifyConsumerTrait;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SeedReminderScenariosCommandTest extends KernelTestCase
{
    use SimulatesNotifyConsumerTrait;

    private const array SURVEY_TABLES = [
        \App\Entity\Domestic\Survey::class => 'domestic_survey',
        \App\Entity\International\Survey::class => 'international_survey',
        \App\Entity\PreEnquiry\PreEnquiry::class => 'pre_enquiry',
        \App\Entity\RoRo\Survey::class => 'roro_survey',
    ];

    private const array NOTIFICATION_TABLES = [
        \App\Entity\Domestic\Survey::class => 'domestic_survey_notification',
        \App\Entity\International\Survey::class => 'international_survey_notification',
        \App\Entity\PreEnquiry\PreEnquiry::class => 'pre_enquiry_notification',
        \App\Entity\RoRo\Survey::class => 'roro_survey_notification',
    ];

    protected function setUp(): void
    {
        static::getContainer()->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
    }

    /**
     * Every command invocation is a separate process in real life, so each starts from a clean identity map. Otherwise
     * collections loaded earlier (e.g. a survey's notes, before its chased note was inserted with DBAL) are reused stale.
     */
    private function tester(): CommandTester
    {
        static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->clear();
        return new CommandTester((new Application(static::$kernel))->find('app:dev:seed-reminder-scenarios'));
    }

    public function testCheckBaselinePassesOnTheReferenceData(): void
    {
        $tester = $this->tester();
        self::assertSame(0, $tester->execute(['--check-baseline' => true]));
        self::assertStringContainsString('ready for seeding', $tester->getDisplay());
    }

    public function testCheckBaselineFailsOnASeededDatabase(): void
    {
        $this->seedNow();
        $tester = $this->tester();
        self::assertNotSame(0, $tester->execute(['--check-baseline' => true]));
        self::assertStringContainsString('Not ready for seeding', $tester->getDisplay());
        self::assertStringContainsString('Recreate', $tester->getDisplay());
    }

    private const string NOW = '2026-09-10 09:00:00'; // a Thursday, mid-month

    /**
     * APP_IN_PROGRESS_REMINDERS_FROM is resolved when DomesticInProgressReminders is first built, so it's set as an
     * environment variable before anything builds that service (loading the fixtures doesn't). tearDown clears it:
     * other tests rely on it being unset.
     */
    private function seedNow(string $now = self::NOW, string $from = '2026-08-15', array $extra = []): CommandTester
    {
        $_SERVER['APP_IN_PROGRESS_REMINDERS_FROM'] = $_ENV['APP_IN_PROGRESS_REMINDERS_FROM'] = $from;
        static::getContainer()->set('clock', new \Symfony\Component\Clock\MockClock($now, 'Europe/London'));
        $tester = $this->tester();
        try {
            $tester->execute(['--force' => true, ...$extra]);
        } finally {
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->clear(); // end of this "process"
        }
        return $tester;
    }

    protected function tearDown(): void
    {
        unset($_SERVER['APP_IN_PROGRESS_REMINDERS_FROM'], $_ENV['APP_IN_PROGRESS_REMINDERS_FROM']);
        parent::tearDown();
    }

    private function manifest(): ?\App\Utility\Rehearsal\SeedManifest
    {
        return static::getContainer()->get(\App\Utility\Rehearsal\SeedManifestStore::class)->find();
    }

    private function countRows(string $sql, array $params = []): int
    {
        return (int) static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->getConnection()->fetchOne($sql, $params);
    }

    public function testSeedsEveryScenarioAndPrintsTheTable(): void
    {
        $tester = $this->seedNow();
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());

        $manifest = $this->manifest();
        self::assertSame(array_map(fn($s) => $s->name, \App\Utility\Rehearsal\ScenarioCatalogue::all()), array_keys($manifest->scenarios));
        self::assertSame(['from' => '2026-08-15', 'disabled' => [], 'smsCap' => 20000], $manifest->settings);
        foreach (['csrgt:r1-due', 'ip:r1-sms', 'roro:r2-due', 'pre:r2-due'] as $name) {
            self::assertStringContainsString($name, $tester->getDisplay());
        }
        self::assertSame(15, $this->countRows('SELECT COUNT(*) FROM domestic_survey'));
        self::assertSame(5, $this->countRows('SELECT COUNT(*) FROM international_survey'));
        self::assertSame(3, $this->countRows('SELECT COUNT(*) FROM pre_enquiry'));
        self::assertSame(2, $this->countRows('SELECT COUNT(*) FROM roro_survey'));
    }

    public function testOnlyInviteScenariosQueueInvites(): void
    {
        $this->seedNow();
        // csrgt:invite letter + email, irhs:invite letter + email, pre:invite letter
        self::assertSame(5, $this->countRows('SELECT COUNT(*) FROM messenger_messages'));
        self::assertSame(2, $this->countRows("SELECT COUNT(*) FROM domestic_survey_notification WHERE type = 'invite' AND sent_at > ?", [self::NOW]), 'only csrgt:invite gets a new (4h-delayed) invite row');
    }

    public function testChasedNoteKeepsItsSeededDate(): void
    {
        $this->seedNow();
        $id = $this->manifest()->scenarios['ip:chased-recently']['id'];
        self::assertSame('2026-09-08 09:00:00', static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->getConnection()
            ->fetchOne('SELECT created_at FROM domestic_survey_note WHERE survey_id = ? AND was_chased = 1', [$id]));
    }

    public function testBoundaryScenariosSitEitherSideOfFrom(): void
    {
        $this->seedNow();
        $end = fn(string $name) => static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->getConnection()
            ->fetchOne('SELECT survey_period_end FROM domestic_survey WHERE id = ?', [$this->manifest()->scenarios[$name]['id']]);
        self::assertSame('2026-08-15', substr($end('ip:r1-on-from'), 0, 10));
        self::assertSame('2026-08-14', substr($end('ip:before-from'), 0, 10));
    }

    public function testBaselineOperatorAndRoutesAreUntouched(): void
    {
        $routesBefore = $this->countRows('SELECT COUNT(*) FROM route');
        $this->seedNow();
        self::assertSame($routesBefore, $this->countRows('SELECT COUNT(*) FROM route'));
        self::assertSame(0, $this->countRows("SELECT COUNT(*) FROM roro_operator_route r JOIN roro_operator o ON o.id = r.operator_id WHERE o.name = 'Stena Line - Irish'"));
        self::assertSame(2, $this->countRows("SELECT COUNT(*) FROM roro_operator_route r JOIN roro_operator o ON o.id = r.operator_id WHERE o.name = 'SEED reminder operator'"));
    }

    /** @return iterable<string, array{0: string, 1: string, 2: string}> */
    public static function refusals(): iterable
    {
        yield 'saturday' => ['2026-09-12 09:00:00', '2026-08-15', 'weekend'];
        yield 'last day of the month' => ['2026-09-30 09:00:00', '2026-08-15', 'month'];
        yield 'from too recent' => [self::NOW, '2026-09-05', 'APP_IN_PROGRESS_REMINDERS_FROM'];
        yield 'from unset' => [self::NOW, '', 'APP_IN_PROGRESS_REMINDERS_FROM'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('refusals')]
    public function testRefusals(string $now, string $from, string $message): void
    {
        $tester = $this->seedNow($now, $from);
        self::assertNotSame(0, $tester->getStatusCode());
        self::assertStringContainsString($message, $tester->getDisplay());
        self::assertNull($this->manifest());
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM domestic_survey'));
    }

    public function testRefusesWithoutTwoActiveBaselineRoutes(): void
    {
        static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->getConnection()->executeStatement('UPDATE route SET is_active = 0');
        $tester = $this->seedNow();
        self::assertNotSame(0, $tester->getStatusCode());
        self::assertStringContainsString('two active routes', $tester->getDisplay());
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM domestic_survey'), 'nothing is left behind');
    }

    public function testAllOrNothing(): void
    {
        $container = static::getContainer();
        $store = $this->createMock(\App\Utility\Rehearsal\SeedManifestStore::class);
        $store->method('find')->willReturn(null);
        $store->method('write')->willThrowException(new \RuntimeException('injected failure after the invites were queued'));
        $container->set(\App\Utility\Rehearsal\SeedManifestStore::class, $store);

        // The command only catches SeedRefusedException, and CommandTester rethrows anything else.
        try {
            $this->seedNow();
            self::fail('expected the injected failure to propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('injected failure after the invites were queued', $e->getMessage());
        }
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM domestic_survey'));
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM messenger_messages'), 'the queued invites rolled back too');
        self::assertSame([], $container->get(\App\Utility\Rehearsal\BaselineGuard::class)->problems());
    }

    public function testConfirmationIsRequiredWithoutForce(): void
    {
        $container = static::getContainer();
        $container->set('clock', new \Symfony\Component\Clock\MockClock(self::NOW, 'Europe/London'));
        $tester = $this->tester();
        $tester->setInputs(['not-the-database-name']);
        self::assertNotSame(0, $tester->execute([]));
        self::assertNull($this->manifest());
    }

    public function testEachScenarioHasItsCataloguedStateAndHistory(): void
    {
        $this->seedNow();
        $manifest = $this->manifest();
        $connection = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->getConnection();
        $tables = self::SURVEY_TABLES;
        $notificationTables = self::NOTIFICATION_TABLES;
        $historyCount = 0;
        foreach (\App\Utility\Rehearsal\ScenarioCatalogue::all() as $scenario) {
            $entry = $manifest->scenarios[$scenario->name];
            // Invites are persisted in state "new"; the lifecycle listener moves them to invitation_pending.
            $expectedState = $scenario->invite ? \App\Entity\SurveyStateInterface::STATE_INVITATION_PENDING : $scenario->state;
            self::assertSame($expectedState, $connection->fetchOne("SELECT state FROM {$tables[$entry['class']]} WHERE id = ?", [$entry['id']]), $scenario->name);

            $history = $connection->fetchAllAssociative("SELECT type, sent_at FROM {$notificationTables[$entry['class']]} WHERE survey_id = ? AND sent_at <= ? ORDER BY sent_at", [$entry['id'], self::NOW]);
            $expected = array_map(fn(array $h) => ['type' => $h[0]->value, 'sent_at' => (new \DateTimeImmutable(self::NOW))->modify("-{$h[1]} days")->format('Y-m-d H:i:s')], $scenario->history);
            usort($expected, fn($a, $b) => $a['sent_at'] <=> $b['sent_at']);
            self::assertSame($expected, $history, $scenario->name);
            $historyCount += count($scenario->history);
        }

        self::assertCount($historyCount, $manifest->historyRowIds);
        foreach ($notificationTables as $table) {
            $ids[] = $connection->fetchFirstColumn("SELECT id FROM {$table} WHERE sent_at <= ?", [self::NOW]);
        }
        $ids = array_merge(...$ids);
        sort($ids);
        $recorded = $manifest->historyRowIds;
        sort($recorded);
        self::assertSame($ids, $recorded, 'the manifest records exactly the seeded history rows');
    }

    private function runAllReminders(): void
    {
        $c = static::getContainer();
        // Separate command runs in real life: start from a clean identity map, so the DBAL-inserted chased note and
        // every collection are loaded fresh rather than reused from the seeding stage.
        $em = $c->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em->clear();
        $runner = $c->get(\App\Utility\Notification\AutomatedNotificationsRunner::class);
        $runner->run();
        $em->clear();
        $runner->runOne($c->get(\App\Utility\Reminder\DomesticInProgressReminders::class));
        $em->clear();
    }

    public function testRefusesToSeedOverAnExistingSeed(): void
    {
        $this->seedNow();
        $this->runAllReminders();
        $before = [];
        foreach (['domestic_survey', 'domestic_survey_notification', 'messenger_messages', 'audit_log', 'sms_usage'] as $table) {
            $before[$table] = $this->countRows("SELECT COUNT(*) FROM {$table}");
        }
        $seededAt = $this->manifest()->seededAt;

        $tester = $this->tester();
        self::assertNotSame(0, $tester->execute(['--force' => true]));
        self::assertStringContainsString('already holds a seed', $tester->getDisplay());
        self::assertStringContainsString('Recreate', $tester->getDisplay());

        foreach ($before as $table => $count) {
            self::assertSame($count, $this->countRows("SELECT COUNT(*) FROM {$table}"), "{$table} is untouched");
        }
        self::assertEquals($seededAt, $this->manifest()->seededAt, 'the original seed is kept');
    }

    public function testRefusesToSeedWhenOperationalDataIsPresent(): void
    {
        static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->getConnection()->insert('sms_usage', ['financial_year' => 2026, 'sent' => 3]);
        $tester = $this->seedNow();
        self::assertNotSame(0, $tester->getStatusCode());
        self::assertStringContainsString('sms_usage', $tester->getDisplay());
        self::assertStringContainsString('Recreate', $tester->getDisplay());
        self::assertSame(3, $this->countRows('SELECT sent FROM sms_usage'), 'nothing deleted');
        self::assertNull($this->manifest());
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM domestic_survey'));
    }

    public function testReportRefusesWithoutASeed(): void
    {
        $tester = $this->tester();
        self::assertNotSame(0, $tester->execute(['--report' => true]));
        self::assertStringContainsString('No seed', $tester->getDisplay());
    }

    public function testReportPrintsTheTable(): void
    {
        $this->seedNow();
        $this->runAllReminders();
        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em->clear();
        $this->simulateConsumer($em, $this->manifest());
        $em->clear();

        $tester = $this->tester();
        self::assertSame(0, $tester->execute(['--report' => true]), $tester->getDisplay());
        self::assertStringContainsString('Effective settings', $tester->getDisplay());
        self::assertStringContainsString('ip:r1-sms', $tester->getDisplay());
        self::assertStringContainsString('⏳', $tester->getDisplay());
    }
}
