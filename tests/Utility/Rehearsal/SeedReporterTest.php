<?php

namespace App\Tests\Utility\Rehearsal;

use App\Tests\DataFixtures\Rehearsal\BaselineReferenceFixtures;
use App\Utility\Notification\AutomatedNotificationsRunner;
use App\Utility\Rehearsal\ScenarioOutcome;
use App\Utility\Rehearsal\ScenarioSeeder;
use App\Utility\Rehearsal\SeedReporter;
use App\Utility\Reminder\DomesticInProgressReminders;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

class SeedReporterTest extends KernelTestCase
{
    use SimulatesNotifyConsumerTrait;

    private const string NOW = '2026-09-10 09:00:00';
    private const array SETTINGS = ['from' => '2026-08-15', 'disabled' => [], 'smsCap' => 20000];

    private function at(string $offset = ''): \DateTimeImmutable
    {
        return (new \DateTimeImmutable(self::NOW, new \DateTimeZone('Europe/London')))->modify($offset ?: '+0 seconds');
    }

    public function testEveryScenarioOutcomeHolds(): void
    {
        $c = static::getContainer();
        $c->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
        $c->set('clock', new MockClock(self::NOW, 'Europe/London'));
        $em = $c->get(EntityManagerInterface::class);

        $manifest = $c->get(ScenarioSeeder::class)->seed($this->at(), self::SETTINGS);
        $runner = $c->get(AutomatedNotificationsRunner::class);
        $runner->run();
        $em->clear(); // separate processes in real life
        $runner->runOne($c->get(DomesticInProgressReminders::class)->withRemindersFrom(self::SETTINGS['from']));
        $em->clear();
        $this->simulateConsumer($em, $manifest);
        $em->clear();

        $reporter = $c->get(SeedReporter::class);
        $verdicts = [];
        foreach ($reporter->outcomes($manifest, $this->at('+10 minutes')) as $o) {
            $verdicts[$o->scenario->name] = $o->verdict;
        }
        foreach ($verdicts as $name => $verdict) {
            $expected = str_ends_with($name, ':invite') ? ScenarioOutcome::WAIT : ScenarioOutcome::PASS;
            self::assertSame($expected, $verdict, "{$name}");
        }

        // after the 4h invite delay, the consumer sends the invites too
        $this->simulateConsumer($em, $manifest, includeInvites: true);
        $em->clear();
        foreach ($reporter->outcomes($manifest, $this->at('+5 hours')) as $o) {
            self::assertSame(ScenarioOutcome::PASS, $o->verdict, $o->scenario->name.': '.$o->detail);
        }
    }

    /**
     * The local php83 container's PHP timezone is UTC, and its NativeClock gives UTC times; prod runs in Europe/London.
     * 2026-09-10 is in BST, where the two differ by an hour. The report must not depend on which one wrote the rows.
     */
    public function testOutcomesHoldWithAUtcClockDuringBst(): void
    {
        $c = static::getContainer();
        $c->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
        $c->set('clock', new MockClock('2026-09-10 08:00:00', 'UTC')); // = 09:00 London
        $em = $c->get(EntityManagerInterface::class);

        $manifest = $c->get(ScenarioSeeder::class)->seed(new \DateTimeImmutable('2026-09-10 08:00:00', new \DateTimeZone('UTC')), self::SETTINGS);
        $em->clear();
        $runner = $c->get(AutomatedNotificationsRunner::class);
        $runner->run();
        $em->clear();
        $runner->runOne($c->get(DomesticInProgressReminders::class)->withRemindersFrom(self::SETTINGS['from']));
        $em->clear();
        $this->simulateConsumer($em, $manifest, includeInvites: true);
        $em->clear();

        foreach ($c->get(SeedReporter::class)->outcomes($manifest, new \DateTimeImmutable('2026-09-10 13:30:00', new \DateTimeZone('UTC'))) as $o) {
            self::assertSame(ScenarioOutcome::PASS, $o->verdict, $o->scenario->name.': '.$o->detail);
        }
    }

    public function testADuplicateSendFails(): void
    {
        $c = static::getContainer();
        $c->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
        $c->set('clock', new MockClock(self::NOW, 'Europe/London'));
        $em = $c->get(EntityManagerInterface::class);
        $manifest = $c->get(ScenarioSeeder::class)->seed($this->at(), self::SETTINGS);
        $em->clear();
        $runner = $c->get(AutomatedNotificationsRunner::class);
        $runner->run();
        $em->clear();

        // send csrgt:r1-due's reminder 1 a second time, as a buggy run would
        $survey = $em->find(\App\Entity\Domestic\Survey::class, $manifest->scenarios['csrgt:r1-due']['id']);
        $em->wrapInTransaction(fn() => $c->get(\App\Utility\Notification\NotificationSender::class)->send($survey, \App\Utility\Notification\NotificationType::REMINDER_1));
        $em->clear();
        $this->simulateConsumer($em, $manifest);
        $em->clear();

        $byName = [];
        foreach ($c->get(SeedReporter::class)->outcomes($manifest, $this->at('+10 minutes')) as $o) {
            $byName[$o->scenario->name] = $o;
        }
        self::assertSame(ScenarioOutcome::FAIL, $byName['csrgt:r1-due']->verdict);
        self::assertStringContainsString('sent more than once', $byName['csrgt:r1-due']->detail);
    }

    public function testAMissingRowFails(): void
    {
        $c = static::getContainer();
        $c->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
        $c->set('clock', new MockClock(self::NOW, 'Europe/London'));
        $manifest = $c->get(ScenarioSeeder::class)->seed($this->at(), self::SETTINGS);

        $outcomes = $c->get(SeedReporter::class)->outcomes($manifest, $this->at('+10 minutes')); // reminders never run
        $byName = [];
        foreach ($outcomes as $o) {
            $byName[$o->scenario->name] = $o;
        }
        self::assertSame(ScenarioOutcome::FAIL, $byName['csrgt:r1-due']->verdict);
        self::assertStringContainsString('reminder-1/letter', $byName['csrgt:r1-due']->detail);
        self::assertSame(ScenarioOutcome::PASS, $byName['ip:closed']->verdict, 'expect-nothing scenarios pass when nothing was sent');
    }

    public function testPendingRowsHintAtMessengerConsume(): void
    {
        $c = static::getContainer();
        $c->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
        $c->set('clock', new MockClock(self::NOW, 'Europe/London'));
        $em = $c->get(EntityManagerInterface::class);
        $manifest = $c->get(ScenarioSeeder::class)->seed($this->at(), self::SETTINGS);
        $runner = $c->get(AutomatedNotificationsRunner::class);
        $runner->run(); // queues the row, but nothing has consumed it yet: it stays "pending"
        $em->clear();

        $byName = [];
        foreach ($c->get(SeedReporter::class)->outcomes($manifest, $this->at('+10 minutes')) as $o) {
            $byName[$o->scenario->name] = $o;
        }
        self::assertSame(ScenarioOutcome::FAIL, $byName['csrgt:r1-due']->verdict);
        self::assertStringContainsString('pending', $byName['csrgt:r1-due']->detail);
        self::assertStringContainsString('messenger:consume', $byName['csrgt:r1-due']->detail);
        self::assertStringContainsString('A6', $byName['csrgt:r1-due']->detail);
    }

    public function testStaleAndSettingsGuards(): void
    {
        $c = static::getContainer();
        $c->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
        $c->set('clock', new MockClock(self::NOW, 'Europe/London'));
        $manifest = $c->get(ScenarioSeeder::class)->seed($this->at(), self::SETTINGS);
        $reporter = $c->get(SeedReporter::class);

        self::assertNull($reporter->getRefusalReason($manifest, $this->at('+23 hours')));
        self::assertStringContainsString('Recreate the scratch database', (string) $reporter->getRefusalReason($manifest, $this->at('+25 hours')));
        self::assertNull($reporter->settingsWarning(self::SETTINGS, self::SETTINGS));
        self::assertStringContainsString('APP_IN_PROGRESS_REMINDERS_FROM', (string) $reporter->settingsWarning(self::SETTINGS, ['from' => '2026-08-01'] + self::SETTINGS));
    }
}
