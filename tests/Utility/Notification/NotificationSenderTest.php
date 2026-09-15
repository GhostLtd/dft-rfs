<?php

namespace App\Tests\Utility\Notification;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Embeddable\LongAddress;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryNotification;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\EmailBlacklistService;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationRecorder;
use App\Utility\Notification\NotificationSender;
use App\Utility\Notification\NotificationType;
use App\Utility\NotificationInterceptionService;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class NotificationSenderTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private NotificationSender $sender;
    /** @var array<array{0: object, 1: array}> */
    private array $dispatched = [];

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $message, array $stamps = []) {
            $this->dispatched[] = [$message, $stamps];
            return new Envelope($message, $stamps);
        });
        $interception = $this->createMock(NotificationInterceptionService::class);
        $interception->method('getAllEmailsForSurvey')->willReturnCallback(fn(object $s) => $s->getArrayOfInvitationEmails());
        $blacklist = $this->createMock(EmailBlacklistService::class);
        $blacklist->method('filterBlacklistedEmails')->willReturnCallback(fn(array $e) => array_values(array_filter($e, fn($x) => $x !== 'blocked@example.com')));
        $personalisation = $this->createMock(PersonalisationHelper::class);
        $personalisation->method('getForEntity')->willReturn(['x' => 'y']);

        $this->sender = new NotificationSender(
            new NotificationRecorder($this->entityManager),
            $bus,
            $personalisation,
            $interception,
            $blacklist,
            new MockClock('2026-09-10 09:00:00', 'Europe/London'),
            new NullLogger(),
        );
    }

    public function testReminderRecordsRowAndDispatchesWithoutDelayStampOrFlush(): void
    {
        $survey = (new Survey())->setId('survey-1')->setState(Survey::STATE_INVITATION_SENT)->setInvitationEmails('a@example.com');
        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(SurveyNotification::class));
        $this->entityManager->expects(self::never())->method('flush');

        $channels = $this->sender->send($survey, NotificationType::REMINDER_1);

        self::assertSame([NotificationChannel::EMAIL], $channels, 'no address => letter skipped');
        [$email, $stamps] = $this->dispatched[0];
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('reminder-1', $email->getEventName());
        self::assertSame([], $stamps);
        self::assertSame(SurveyNotification::class, $email->getOriginatingEntityClass());
        self::assertNull($email->getOriginatingEntityId(), 'no persist in a unit test, so no id yet');
        $row = $survey->getNotificationsOfType(NotificationType::REMINDER_1)[0];
        self::assertEquals(new \DateTime('2026-09-10 09:00:00', new \DateTimeZone('Europe/London')), $row->getSentAt());
    }

    public function testNothingDispatchedRecordsNothing(): void
    {
        $survey = (new Survey())->setState(Survey::STATE_INVITATION_SENT); // no address, no emails
        $this->entityManager->expects(self::never())->method('persist');
        self::assertSame([], $this->sender->send($survey, NotificationType::REMINDER_1));
    }

    public function testPreEnquiryWithEmailsStillGetsALetterOnly(): void
    {
        $survey = (new PreEnquiry())->setId('pre-1')->setState(Survey::STATE_INVITATION_SENT)->setInvitationEmails('a@example.com')
            ->setInvitationAddress((new LongAddress())->setLine1('Co')->setLine2('1 Road')->setPostcode('BA1 1HR'));
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            if ($entity instanceof PreEnquiryNotification) {
                $entity->setId('generated-notification-1');
            }
        });

        self::assertSame([NotificationChannel::LETTER], $this->sender->send($survey, NotificationType::REMINDER_1), 'PRE has no email template');
        self::assertCount(1, $this->dispatched);
        self::assertInstanceOf(Letter::class, $this->dispatched[0][0]);
    }

    public function testPreEnquiryWithoutAddressSendsNothing(): void
    {
        $survey = (new PreEnquiry())->setState(Survey::STATE_INVITATION_SENT)->setInvitationEmails('a@example.com');
        self::assertSame([], $this->sender->send($survey, NotificationType::REMINDER_1), 'PRE has no email template, and no address here');
    }

    public function testInviteUsesInvitationRecipientsAndDelayStamp(): void
    {
        $survey = (new Survey())
            ->setId('survey-2')
            ->setInvitationEmails('a@example.com, blocked@example.com')
            ->setInvitationAddress((new LongAddress())->setLine1('Co')->setLine2('1 Road')->setPostcode('BA1 1HR'));

        // Letter (unlike Email) requires a non-null originating entity id, and a mocked
        // EntityManager::persist() doesn't invoke the real id generator, so fake it here.
        $idCounter = 0;
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity) use (&$idCounter): void {
            if ($entity instanceof SurveyNotification) {
                $entity->setId('generated-notification-'.++$idCounter);
            }
        });

        $channels = $this->sender->send($survey, NotificationType::INVITE, new \DateInterval('PT4H'));

        self::assertSame([NotificationChannel::LETTER, NotificationChannel::EMAIL], $channels);
        self::assertInstanceOf(Letter::class, $this->dispatched[0][0]);
        self::assertInstanceOf(Email::class, $this->dispatched[1][0]);
        self::assertSame('a@example.com', $this->dispatched[1][0]->getEmailAddress(), 'blacklisted address removed');
        self::assertCount(2, $this->dispatched, 'one email per remaining recipient');

        foreach ($this->dispatched as [, $stamps]) {
            self::assertCount(1, $stamps);
            self::assertInstanceOf(DelayStamp::class, $stamps[0]);
            self::assertSame(4 * 60 * 60 * 1000, $stamps[0]->getDelay());
        }

        $letterRow = $survey->getNotificationsOfType(NotificationType::INVITE, NotificationChannel::LETTER)[0];
        self::assertEquals(new \DateTime('2026-09-10 13:00:00', new \DateTimeZone('Europe/London')), $letterRow->getSentAt(), 'sent_at = now + delay');

        // The Notify reference still names the survey, exactly as the bundle builds it when the survey is the
        // originating entity, even though the originating entity is now the notification row.
        [[$letter], [$email]] = $this->dispatched;
        self::assertSame(SurveyNotification::class, $letter->getOriginatingEntityClass());
        self::assertSame(
            (new Letter('invite', Survey::class, 'survey-2', $survey->getInvitationAddress(), 'template'))->getReference(),
            $letter->getReference(),
        );
        self::assertSame(
            (new Email('invite', Survey::class, 'survey-2', 'a@example.com', 'template'))->getReference(),
            $email->getReference(),
        );
        self::assertStringStartsWith(Survey::class.'::survey-2::', $email->getReference());
    }

    public function testManualReminderUsesResponseContactEmail(): void
    {
        $survey = (new Survey())->setId('survey-3')->setState(Survey::STATE_IN_PROGRESS)->setInvitationEmails('inv@example.com')
            ->setInvitationAddress((new LongAddress())->setLine1('Co')->setLine2('1 Road')->setPostcode('BA1 1HR'));
        $survey->setResponse((new SurveyResponse())->setContactEmail('contact@example.com'));

        self::assertSame([NotificationChannel::EMAIL], $this->sender->send($survey, NotificationType::MANUAL_REMINDER), 'manual reminder is email-only even with an address');
        self::assertSame('contact@example.com', $this->dispatched[0][0]->getEmailAddress());
    }

    public function testManualReminderFallsBackToInvitationEmails(): void
    {
        $survey = (new Survey())->setId('survey-3')->setState(Survey::STATE_IN_PROGRESS)->setInvitationEmails('inv@example.com');
        $survey->setResponse((new SurveyResponse())->setContactEmail(null));

        self::assertSame([NotificationChannel::EMAIL], $this->sender->send($survey, NotificationType::MANUAL_REMINDER));
        self::assertSame('inv@example.com', $this->dispatched[0][0]->getEmailAddress());
    }

    public function testTypeThatDoesNotApplyToTheClassIsAProgrammingError(): void
    {
        $this->expectException(\LogicException::class);
        // Pre-enquiry has no manual-reminder template.
        $this->sender->send((new PreEnquiry())->setId('pre-2')->setState(Survey::STATE_IN_PROGRESS), NotificationType::MANUAL_REMINDER);
    }
}
