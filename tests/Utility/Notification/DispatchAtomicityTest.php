<?php

namespace App\Tests\Utility\Notification;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\Domestic\Survey;
use App\Entity\Embeddable\LongAddress;
use App\Entity\PasscodeUser;
use App\Entity\RoRo\Operator as RoRoOperator;
use App\Entity\Route\Route as RoRoRoute;
use App\Entity\RoRoUser;
use App\Tests\DataFixtures\Notification\InProgressReminderScenarioFixtures;
use App\Tests\DataFixtures\Notification\ReminderScenarioFixtures;
use App\Tests\DataFixtures\RoRo\CountryFixtures;
use App\Tests\DataFixtures\RoRo\OperatorRouteFixtures;
use App\Tests\DataFixtures\RoRo\UserFixtures;
use App\Utility\Notification\AutomatedNotificationsRunner;
use App\Utility\Notification\NotificationQueryHelper;
use App\Utility\Notification\NotificationSender;
use App\Utility\Notification\NotificationType;
use App\Utility\Notification\SmsRecipientDailyCap;
use App\Utility\Reminder\DisabledRemindersHelper;
use App\Utility\Reminder\DomesticInProgressReminders;
use App\Utility\Reminder\DomesticReminders;
use App\Utility\Reminder\InternationalReminders;
use App\Utility\Reminder\ManualReminderHelper;
use App\Utility\Reminder\PreEnquiryReminders;
use App\Utility\Reminder\RoRoReminders;
use App\Utility\RoRo\SurveyCreationHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * A "bus" that sends straight to the real async_notify Doctrine transport -- bypassing routing
 * middleware entirely -- letting N messages reach it before throwing. This still inserts into
 * messenger_messages via the real transport on the ORM connection (the property under test), but
 * never needs a handle on the real message bus service itself: fetching that by any of its aliases
 * (messenger.bus.default, MessageBusInterface, ...) permanently blocks the test container from
 * later overriding "app.test.message_bus" ("already initialized"), because they all resolve to the
 * same shared, cached service.
 *
 * On the dispatch that trips the limit, it flushes the entity manager before throwing, so any
 * notification rows recorded (but not yet flushed) for messages already sent in this same
 * transaction are physically written to the database first; their absence after the run is what
 * proves the surrounding transaction rolled them back, rather than them simply never having
 * reached the connection.
 */
final class FailAfterNBus implements MessageBusInterface
{
    public int $dispatched = 0;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly int $allow,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        if ($this->dispatched >= $this->allow) {
            $this->entityManager->flush();
            throw new \RuntimeException('simulated transport failure');
        }
        $this->dispatched++;

        $transport = $this->container->get('messenger.transport.async_notify');
        assert($transport instanceof TransportInterface);

        return $transport->send(new Envelope($message, $stamps));
    }
}

class DispatchAtomicityTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ReferenceRepository $refs;
    private MockClock $clock;

    private function queueCount(): int
    {
        return (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM messenger_messages');
    }

    private function rowCount(): int
    {
        return (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM domestic_survey_notification');
    }

    /**
     * Boots a fresh kernel and installs the FailAfterNBus override before anything can resolve
     * the message bus -- including the fixtures loaded here, whose postPersist listener can itself
     * instantiate NotificationSender. container->set('app.test.message_bus', ...) only
     * succeeds once per kernel boot (a second call, or one after the bus was already resolved
     * through normal DI, throws "already initialized"), so each test starts from a clean kernel
     * and sets its bus exactly once, right at the top. The clock is pinned here too, so every
     * family service and the test's own assertions share one shared, fixed "now".
     */
    /** @param list<class-string> $fixtures */
    private function boot(int $allow, string $now, array $fixtures = [ReminderScenarioFixtures::class]): void
    {
        self::ensureKernelShutdown();
        static::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $container->set('app.test.message_bus', new FailAfterNBus($container, $allow, $this->entityManager));
        $this->clock = new MockClock($now, 'Europe/London');
        $container->set('clock', $this->clock);

        $this->refs = $container->get(DatabaseToolCollection::class)->get()->loadFixtures($fixtures)->getReferenceRepository();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM messenger_messages');
    }

    private function runner(string $disabled): AutomatedNotificationsRunner
    {
        $container = static::getContainer();
        return new AutomatedNotificationsRunner(
            [
                $container->get(DomesticReminders::class),
                $container->get(InternationalReminders::class),
                $container->get(PreEnquiryReminders::class),
                $container->get(RoRoReminders::class),
            ],
            new DisabledRemindersHelper($disabled),
            new NullLogger(),
        );
    }

    public function testSecondChannelFailureRollsBackFirstQueuedMessageAndRows(): void
    {
        $this->boot(1, ReminderScenarioFixtures::NOW);
        $container = static::getContainer();

        // r1:due has invitationEmails but no invitation address, which would only produce an
        // EMAIL send (one message, nothing for a second channel to throw against). Give it an
        // address too, so its reminder-1 dispatch sends LETTER then EMAIL. This is a plain update
        // on an existing entity (no insert), so it doesn't touch the message bus itself.
        $survey = $this->refs->getReference('r1:due', Survey::class);
        $r1DueId = $survey->getId();
        $survey->setInvitationAddress((new LongAddress())->setLine1('Co')->setLine2('1 Road')->setPostcode('BA1 1HR'));
        $this->entityManager->flush();

        $rowsBefore = $this->rowCount();

        // DomesticReminders::dueForReminder1() has no ORDER BY, so another CSRGT reminder-1 survey
        // (r1:tolerance -- no address, email-only) may be processed before or after r1:due.
        // Work out how many *other* CSRGT reminder-1 surveys are due, so the assertions below
        // don't assume a processing order -- they only assert that r1:due itself never keeps
        // anything, and bound what the (at most one, email-only) others could have queued.
        $others = count(array_filter($container->get(DomesticReminders::class)->dueForReminder1($this->clock->now()), fn(Survey $s) => $s->getId() !== $r1DueId));

        try {
            // RoRo is not in ReminderScenarioFixtures, so its family simply finds nothing due.
            $this->runner('irhs,pre-enquiry,roro')->run();
            self::fail('expected the simulated failure to propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('simulated transport failure', $e->getMessage());
        }

        $connection = $this->entityManager->getConnection();

        self::assertSame(0, (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM domestic_survey_notification WHERE survey_id = ? AND type = ?',
            [$r1DueId, NotificationType::REMINDER_1->value],
        ), 'r1:due never keeps a REMINDER_1 notification row, whichever survey the run reached first');

        self::assertLessThanOrEqual($others, $this->queueCount(), 'only other due surveys can have left a message queued before the failure');
        self::assertLessThanOrEqual($others, $this->rowCount() - $rowsBefore, 'only other due surveys can have left a notification row committed before the failure');

        // Decisive for atomicity: only r1:due has an address, so it is the only survey whose
        // dispatch could ever produce a Letter. It never survives -- either r1:due's dispatch was
        // never reached, or it was reached and its own transaction rolled the letter back
        // together with the email that failed straight after it.
        self::assertSame(0, (int) $connection->fetchOne("SELECT COUNT(*) FROM messenger_messages WHERE body LIKE '%Letter%'"), 'no letter was ever left queued');
    }

    public function testMultiSurveyCreationInvitesEachAndRollsBackWholeFlushOnFailure(): void
    {
        // One decorator instance serves both phases below: the happy path's two letters are
        // dispatches 1 and 2 (both allowed), the failure phase's first letter is dispatch 3 (also
        // allowed), and its second is dispatch 4 (throws) -- so allow=3 covers the whole test
        // without ever needing a second container->set() call.
        $this->boot(3, ReminderScenarioFixtures::NOW);

        $make = fn(string $name, string $code1) => (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2026-08-25'))->setSurveyPeriodEnd(new \DateTime('2026-08-31'))
            ->setIsNorthernIreland(false)->setRegistrationMark(strtoupper(substr(md5($name), 0, 7)))
            ->setInvitationAddress((new LongAddress())->setLine1('Co')->setLine2('1 Road')->setPostcode('BA1 1HR'))
            ->setPasscodeUser((new PasscodeUser())->setUsername($code1)->setPlainPassword('x'));

        // happy path: two surveys, one flush, two letters queued, two invite rows
        $rowsBefore = $this->rowCount();
        $this->entityManager->persist($make('bulk-a', '15000001'));
        $this->entityManager->persist($make('bulk-b', '15000002'));
        $this->entityManager->flush();
        self::assertSame(2, $this->queueCount());
        self::assertSame($rowsBefore + 2, $this->rowCount());

        // failure part-way: bulk-c's letter (dispatch 3) is still allowed, bulk-d's (dispatch 4)
        // throws inside postPersist => the whole flush rolls back, including bulk-c's insert and
        // its already-dispatched message and notification row.
        $this->entityManager->getConnection()->executeStatement('DELETE FROM messenger_messages');
        $this->entityManager->clear();
        $surveysBefore = (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM domestic_survey');
        $rowsBeforeFailure = $this->rowCount();

        $this->entityManager->persist($make('bulk-c', '15000003'));
        $this->entityManager->persist($make('bulk-d', '15000004'));
        try {
            $this->entityManager->flush();
            self::fail('expected failure');
        } catch (\RuntimeException) {
        }

        self::assertSame(0, $this->queueCount());
        self::assertSame($surveysBefore, (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM domestic_survey'), 'no survey from the failed flush persisted');
        self::assertSame($rowsBeforeFailure, $this->rowCount(), 'no notification row from the failed flush persisted, including bulk-c\'s already-dispatched one');
    }

    /**
     * A RoRo operator group (both surveys, both users) is one transaction, like a CSRGT survey's
     * per-channel dispatch is. allow=1 lets the first user's digest email reach messenger_messages
     * via the real transport before the second user's dispatch throws; FailAfterNBus flushes the
     * entity manager immediately before throwing, so both surveys' reminder-1 rows -- recorded but
     * not yet flushed by RoRoReminders at that point -- are physically written to the database
     * ahead of the failure. Their absence afterwards, alongside the first user's already-inserted
     * message, is therefore real proof that the whole group -- message and rows alike -- rolled
     * back together, not just the second user's failed send.
     */
    public function testGroupedRuleRollsBackWholeOperatorGroupOnSecondMessageFailure(): void
    {
        $this->boot(1, '2023-10-15 09:00:00', [CountryFixtures::class, OperatorRouteFixtures::class, UserFixtures::class]);
        $container = static::getContainer();

        $operator = $this->refs->getReference('roro:operator:1', RoRoOperator::class);
        $route = $this->refs->getReference('roro:route:1', RoRoRoute::class);

        // UserFixtures gives the operator one user; add a second so the group's dispatch sends
        // two messages, and the failure lands between them rather than on the only one.
        $secondUser = (new RoRoUser())->setUsername('second@example.com');
        $operator->addUser($secondUser);
        $this->entityManager->persist($secondUser);
        $this->entityManager->flush();
        self::assertCount(2, $operator->getUsers());

        $surveyCreationHelper = $container->get(SurveyCreationHelper::class);
        $operatorRoute = new OperatorRoute($operator->getId(), $route->getId());
        $surveyA = $surveyCreationHelper->createSurvey(new \DateTime('2023-09-01 00:00:00'), $operatorRoute);
        $surveyB = $surveyCreationHelper->createSurvey(new \DateTime('2023-09-15 00:00:00'), $operatorRoute);
        $this->entityManager->persist($surveyA);
        $this->entityManager->persist($surveyB);
        $this->entityManager->flush();
        [$surveyAId, $surveyBId] = [$surveyA->getId(), $surveyB->getId()];

        try {
            $this->runner('csrgt,irhs,pre-enquiry')->run();
            self::fail('expected the simulated failure to propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('simulated transport failure', $e->getMessage());
        }

        self::assertSame(0, $this->queueCount(), "the first user's already-inserted message rolls back with the second user's failure: a group is one transaction");

        $connection = $this->entityManager->getConnection();
        self::assertSame(0, (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM roro_survey_notification WHERE survey_id IN (?, ?) AND type = ?',
            [$surveyAId, $surveyBId, NotificationType::REMINDER_1->value],
        ), 'neither survey keeps a reminder-1 row: FailAfterNBus flushed both rows to the database before throwing, so their absence now proves the whole operator group rolled back, not merely that they were never written');
    }

    /**
     * Each operator group is its own transaction, so a failure in one group must not undo a group
     * committed earlier in the same run. Two operators, two users and two due surveys each; allow=2
     * lets whichever group the runner reaches first send both of its emails and commit, and the
     * next group's first dispatch throws. RoRoReminders::dueForReminder1() has no ORDER BY, so the assertions do not
     * assume which operator went first: exactly one operator keeps its two messages and its two
     * reminder-1 rows, and the other keeps nothing.
     */
    public function testGroupedRuleFailureLeavesEarlierCommittedOperatorGroupIntact(): void
    {
        $this->boot(2, '2023-10-15 09:00:00', [CountryFixtures::class, OperatorRouteFixtures::class, UserFixtures::class]);
        $container = static::getContainer();

        $route = $this->refs->getReference('roro:route:1', RoRoRoute::class);
        $operatorOne = $this->refs->getReference('roro:operator:1', RoRoOperator::class);
        $operatorOne->addUser((new RoRoUser())->setUsername('one-second@example.com'));

        $operatorTwo = (new RoRoOperator())->setName('Second operator')->setCode(1012)->setIsActive(true)->addRoute($route);
        $operatorTwo->addUser((new RoRoUser())->setUsername('two-first@example.com'));
        $operatorTwo->addUser((new RoRoUser())->setUsername('two-second@example.com'));
        $this->entityManager->persist($operatorTwo);
        foreach ([...$operatorOne->getUsers(), ...$operatorTwo->getUsers()] as $user) {
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();
        self::assertCount(2, $operatorOne->getUsers());
        self::assertCount(2, $operatorTwo->getUsers());

        $surveyCreationHelper = $container->get(SurveyCreationHelper::class);
        $surveyIds = [];
        foreach (['one' => $operatorOne, 'two' => $operatorTwo] as $key => $operator) {
            $operatorRoute = new OperatorRoute($operator->getId(), $route->getId());
            foreach (['2023-09-01 00:00:00', '2023-09-15 00:00:00'] as $start) {
                $survey = $surveyCreationHelper->createSurvey(new \DateTime($start), $operatorRoute);
                $this->entityManager->persist($survey);
                $surveyIds[$key][] = $survey->getId();
            }
        }
        $this->entityManager->flush();

        try {
            $this->runner('csrgt,irhs,pre-enquiry')->run();
            self::fail('expected the simulated failure to propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('simulated transport failure', $e->getMessage());
        }

        $connection = $this->entityManager->getConnection();
        $rowsFor = fn(array $ids): int => (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM roro_survey_notification WHERE survey_id IN (?, ?) AND type = ?',
            [...$ids, NotificationType::REMINDER_1->value],
        );
        $messagesTo = fn(string $email): int => (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM messenger_messages WHERE body LIKE ?', ["%{$email}%"],
        );

        $committed = $rowsFor($surveyIds['one']) > 0 ? 'one' : 'two';
        $failed = $committed === 'one' ? 'two' : 'one';
        $users = ['one' => ['test@example.com', 'one-second@example.com'], 'two' => ['two-first@example.com', 'two-second@example.com']];

        self::assertSame(2, $rowsFor($surveyIds[$committed]), "the operator committed before the failure keeps a reminder-1 row on both surveys ({$committed})");
        self::assertSame(0, $rowsFor($surveyIds[$failed]), "the operator whose dispatch failed keeps no rows ({$failed}): its group rolled back");
        self::assertSame(2, $this->queueCount(), 'only the committed group\'s two digest emails remain queued');
        foreach ($users[$committed] as $email) {
            self::assertSame(1, $messagesTo($email), "one queued email for {$email} of the committed operator");
        }
        foreach ($users[$failed] as $email) {
            self::assertSame(0, $messagesTo($email), "no queued email for {$email} of the failed operator");
        }
    }

    /**
     * The SMS channel's allowance unit and ledger row are written inside the survey's transaction; when the SMS
     * dispatch (after the email's) throws, both roll back with the email's queued message and the rows.
     */
    public function testSmsAllowanceAndLedgerRollBackWithTheSurveysTransaction(): void
    {
        $this->boot(1, InProgressReminderScenarioFixtures::NOW, [InProgressReminderScenarioFixtures::class]);
        $container = static::getContainer();
        $connection = $this->entityManager->getConnection();

        // Only ip1:due stays a candidate: it is the one survey with a mobile, so email (allowed) then SMS (throws).
        $surveyId = $this->refs->getReference('ip1:due', Survey::class)->getId();
        $connection->executeStatement("UPDATE domestic_survey SET state = 'closed' WHERE id <> ?", [$surveyId]);

        $family = new DomesticInProgressReminders(
            $this->entityManager,
            $container->get(NotificationQueryHelper::class),
            $container->get(NotificationSender::class),
            $container->get(ManualReminderHelper::class),
            $container->get(SmsRecipientDailyCap::class),
            $this->clock,
            new NullLogger(),
            InProgressReminderScenarioFixtures::FROM,
        );

        try {
            $family->run();
            self::fail('expected the simulated failure to propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('simulated transport failure', $e->getMessage());
        }

        self::assertSame(0, $this->queueCount(), 'the email queued before the failure rolled back');
        self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM domestic_survey_notification WHERE survey_id = ? AND type = ?', [$surveyId, NotificationType::IN_PROGRESS_REMINDER_1->value]));
        self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM sms_usage'), 'the allowance unit rolled back');
        self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM sms_attempt'), 'the ledger row rolled back');
        self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM sms_recipient_day'), 'the per-number unit rolled back');
    }
}
