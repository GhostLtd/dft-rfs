<?php

namespace App\Tests\Utility\Notification;

use App\Entity\Domestic\Survey;
use App\Tests\DataFixtures\Notification\ReminderScenarioFixtures;
use App\Utility\Notification\AutomatedNotificationsRunner;
use App\Utility\Notification\NotificationType;
use App\Utility\Reminder\AutomatedRemindersInterface;
use App\Utility\Reminder\DisabledRemindersHelper;
use App\Utility\Reminder\DomesticReminders;
use App\Utility\Reminder\InternationalReminders;
use App\Utility\Reminder\PreEnquiryReminders;
use App\Utility\Reminder\RoRoReminders;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class AutomatedNotificationsRunnerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ReferenceRepository $refs;
    private MessageBusInterface&MockObject $bus;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->method('dispatch')->willReturnCallback(fn(object $m, array $s = []) => new Envelope($m, $s));
        $container->set('app.test.message_bus', $this->bus);
        $container->set('clock', new MockClock(ReminderScenarioFixtures::NOW, 'Europe/London'));

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->refs = $container->get(DatabaseToolCollection::class)->get()->loadFixtures([ReminderScenarioFixtures::class])->getReferenceRepository();
    }

    /** The real family services (bus mocked, clock pinned) behind a runner with the given families disabled. */
    private function runner(string $disabled = ''): AutomatedNotificationsRunner
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

    private function reload(string $ref): Survey
    {
        $this->entityManager->clear();
        return $this->entityManager->find(Survey::class, $this->refs->getReference($ref, Survey::class)->getId());
    }

    public function testRunSendsDueRemindersAndCommitsRows(): void
    {
        $this->runner()->run();

        self::assertTrue($this->reload('r1:due')->hasNotification(NotificationType::REMINDER_1));
        self::assertTrue($this->reload('r2:due')->hasNotification(NotificationType::REMINDER_2));
        self::assertFalse($this->reload('r1:invite-too-recent')->hasNotification(NotificationType::REMINDER_1));
        self::assertFalse($this->reload('r1:resent-invite-defers')->hasNotification(NotificationType::REMINDER_1));
    }

    public function testDisabledFamilySendsNothing(): void
    {
        $this->runner('csrgt')->run();
        self::assertFalse($this->reload('r1:due')->hasNotification(NotificationType::REMINDER_1));
    }

    public function testEveryEnabledFamilyRunsAndDisabledOnesAreSkipped(): void
    {
        $ran = [];
        $families = [];
        foreach (['csrgt', 'irhs', 'pre-enquiry', 'roro'] as $name) {
            $family = $this->createMock(AutomatedRemindersInterface::class);
            $family->method('getFamily')->willReturn($name);
            $family->method('run')->willReturnCallback(function () use (&$ran, $name) { $ran[] = $name; });
            $families[] = $family;
        }

        (new AutomatedNotificationsRunner($families, new DisabledRemindersHelper('irhs'), new NullLogger()))->run();

        sort($ran);
        self::assertSame(['csrgt', 'pre-enquiry', 'roro'], $ran, 'the disabled family is skipped; the runner promises nothing about order');
    }
}
