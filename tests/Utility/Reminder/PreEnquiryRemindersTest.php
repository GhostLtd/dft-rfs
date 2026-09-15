<?php

namespace App\Tests\Utility\Reminder;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Tests\DataFixtures\Notification\PreEnquiryReminderScenarioFixtures;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use App\Utility\Reminder\PreEnquiryReminders;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class PreEnquiryRemindersTest extends KernelTestCase
{
    use ResolvesFixtureRefsTrait;

    private EntityManagerInterface $entityManager;
    private ReferenceRepository $refs;
    private MessageBusInterface&MockObject $bus;
    private PreEnquiryReminders $reminders;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $container = static::getContainer();

        // The bus override must land before anything resolves the real one (the fixtures below can).
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->method('dispatch')->willReturnCallback(fn(object $m, array $s = []) => new Envelope($m, $s));
        $container->set('app.test.message_bus', $this->bus);

        $clock = new MockClock(PreEnquiryReminderScenarioFixtures::NOW, 'Europe/London');
        $container->set('clock', $clock);
        $this->now = $clock->now();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->refs = $container->get(DatabaseToolCollection::class)->get()->loadFixtures([PreEnquiryReminderScenarioFixtures::class])->getReferenceRepository();

        // The container's family service: its NotificationSender and this test share the 'clock' service set above.
        $this->reminders = $container->get(PreEnquiryReminders::class);
    }

    public function testDueForReminder1WaitsFourteenDaysAndIgnoresSurveyPeriod(): void
    {
        self::assertSame(['r1:due', 'r1:tolerance'], $this->refsOf(PreEnquiry::class, $this->reminders->dueForReminder1($this->now)));
    }

    public function testDueForReminder2(): void
    {
        self::assertSame(['r2:due'], $this->refsOf(PreEnquiry::class, $this->reminders->dueForReminder2($this->now)));
    }

    public function testRunRecordsLetterRowsOnly(): void
    {
        $this->reminders->run();
        $this->entityManager->clear();

        $rows = fn(string $ref, NotificationType $type) => $this->entityManager
            ->find(PreEnquiry::class, $this->refs->getReference($ref, PreEnquiry::class)->getId())
            ->getNotificationsOfType($type);

        self::assertCount(1, $rows('r1:due', NotificationType::REMINDER_1));
        self::assertSame(NotificationChannel::LETTER, $rows('r1:due', NotificationType::REMINDER_1)[0]->getChannel(), 'pre-enquiry is letter-only');
        self::assertCount(1, $rows('r2:due', NotificationType::REMINDER_2));
        self::assertCount(0, $rows('r1:invite-too-recent', NotificationType::REMINDER_1), '9 days is due for CSRGT but not for pre-enquiry');
        self::assertCount(0, $rows('r1:resent-invite-defers', NotificationType::REMINDER_1));
    }
}
