<?php

namespace App\Tests\Utility\Reminder;

use App\Entity\Domestic\Survey;
use App\Tests\DataFixtures\Notification\ReminderScenarioFixtures;
use App\Utility\Notification\NotificationType;
use App\Utility\Reminder\DomesticReminders;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class DomesticRemindersTest extends KernelTestCase
{
    use ResolvesFixtureRefsTrait;

    private EntityManagerInterface $entityManager;
    private ReferenceRepository $refs;
    private MessageBusInterface&MockObject $bus;
    private DomesticReminders $reminders;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $container = static::getContainer();

        // The bus override must land before anything resolves the real one (the fixtures below can).
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->method('dispatch')->willReturnCallback(fn(object $m, array $s = []) => new Envelope($m, $s));
        $container->set('app.test.message_bus', $this->bus);

        $clock = new MockClock(ReminderScenarioFixtures::NOW, 'Europe/London');
        $container->set('clock', $clock);
        $this->now = $clock->now();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->refs = $container->get(DatabaseToolCollection::class)->get()->loadFixtures([ReminderScenarioFixtures::class])->getReferenceRepository();

        // The container's family service: its NotificationSender and this test share the 'clock' service set above.
        $this->reminders = $container->get(DomesticReminders::class);
    }

    public function testDueForReminder1(): void
    {
        self::assertSame(['r1:due', 'r1:tolerance'], $this->refsOf(Survey::class, $this->reminders->dueForReminder1($this->now)));
    }

    public function testDueForReminder2(): void
    {
        self::assertSame(['r2:due'], $this->refsOf(Survey::class, $this->reminders->dueForReminder2($this->now)));
    }

    public function testRunRecordsRowsForDueSurveysOnly(): void
    {
        $this->reminders->run();
        $this->entityManager->clear();

        $has = fn(string $ref, NotificationType $type) => $this->entityManager
            ->find(Survey::class, $this->refs->getReference($ref, Survey::class)->getId())
            ->hasNotification($type);

        self::assertTrue($has('r1:due', NotificationType::REMINDER_1));
        self::assertTrue($has('r1:tolerance', NotificationType::REMINDER_1));
        self::assertTrue($has('r2:due', NotificationType::REMINDER_2));
        self::assertFalse($has('r1:invite-too-recent', NotificationType::REMINDER_1));
        self::assertFalse($has('r1:resent-invite-defers', NotificationType::REMINDER_1));
        self::assertFalse($has('r1:period-end-too-recent', NotificationType::REMINDER_1));
        self::assertFalse($has('r2:r1-too-recent', NotificationType::REMINDER_2));
        self::assertFalse($has('r1:due', NotificationType::REMINDER_2), 'reminder-1 recorded this run has sentAt = now, so reminder-2 cannot follow in the same run');
    }
}
