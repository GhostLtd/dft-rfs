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
use App\Utility\Notification\SmsAllowance;
use App\Utility\Notification\SmsRecipientDailyCap;
use App\Utility\NotificationInterceptionService;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class NotificationSenderTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private SmsAllowance&MockObject $smsAllowance;
    private SmsRecipientDailyCap&MockObject $dailyCap;
    /** @var array<string, int> per-number units taken, by mobile */
    private array $perNumberSent = [];
    private int $perNumberCap = PHP_INT_MAX;
    /** @var list<string> */
    private array $infoLogs = [];
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
        $this->smsAllowance = $this->createMock(SmsAllowance::class);
        $this->dailyCap = $this->createMock(SmsRecipientDailyCap::class);
        $this->dailyCap->method('tryConsume')->willReturnCallback(function (string $mobile): bool {
            if (($this->perNumberSent[$mobile] ?? 0) >= $this->perNumberCap) {
                return false;
            }
            $this->perNumberSent[$mobile] = ($this->perNumberSent[$mobile] ?? 0) + 1;
            return true;
        });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('info')->willReturnCallback(function (string|\Stringable $message): void {
            $this->infoLogs[] = (string) $message;
        });

        $this->sender = new NotificationSender(
            new NotificationRecorder($this->entityManager),
            $bus,
            $personalisation,
            $interception,
            $blacklist,
            $this->smsAllowance,
            $this->dailyCap,
            new MockClock('2026-09-10 09:00:00', 'Europe/London'),
            $logger,
        );
    }

    public function testReminderRecordsRowAndDispatchesWithoutDelayStampOrFlush(): void
    {
        $survey = (new Survey())->setId('survey-1')->setState(Survey::STATE_INVITATION_SENT)->setInvitationEmails('a@example.com');
        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(SurveyNotification::class));
        $this->entityManager->expects(self::never())->method('flush');

        $channels = $this->sender->send($survey, NotificationType::REMINDER_1);

        self::assertSame([NotificationChannel::EMAIL], $channels, 'no address => letter skipped');
        self::assertContains('Notifications: Skipped reminder-1 via letter for survey-1: no address', $this->infoLogs);
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

    /** Persisted notification rows get ids, as the UUID generator would give them. */
    private function idsOnPersist(): void
    {
        $idCounter = 0;
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity) use (&$idCounter): void {
            if ($entity instanceof SurveyNotification) {
                $entity->setId('generated-notification-'.++$idCounter);
            }
        });
    }

    private function inProgressSurvey(?string $contactEmail, ?string $telephone, ?string $invitationEmails = 'inv@example.com'): Survey
    {
        $survey = (new Survey())->setId('survey-ip')->setState(Survey::STATE_IN_PROGRESS);
        if ($invitationEmails !== null) {
            $survey->setInvitationEmails($invitationEmails);
        }
        $survey->setResponse((new SurveyResponse())->setContactEmail($contactEmail)->setContactTelephone($telephone));
        return $survey;
    }

    public function testInProgressReminderSendsEmailToContactAndSmsToNormalisedMobile(): void
    {
        $this->idsOnPersist();
        $this->smsAllowance->method('tryConsume')->willReturn(true);
        $this->smsAllowance->expects(self::once())->method('recordReservation')->with('generated-notification-2', self::anything());
        $survey = $this->inProgressSurvey('contact@example.com', '+44 (0)7700 900123');

        $channels = $this->sender->send($survey, NotificationType::IN_PROGRESS_REMINDER_1);

        self::assertSame([NotificationChannel::EMAIL, NotificationChannel::SMS], $channels);
        self::assertCount(2, $this->dispatched);
        [[$email], [$sms, $stamps]] = $this->dispatched;
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('contact@example.com', $email->getEmailAddress(), 'the contact email, not the invitation emails');
        self::assertInstanceOf(Sms::class, $sms);
        self::assertSame('07700900123', $sms->getPhoneNumber());
        self::assertSame('in-progress-reminder-1', $sms->getEventName());
        self::assertSame('generated-notification-2', $sms->getOriginatingEntityId());
        self::assertSame([], $stamps, 'no delay');
        self::assertCount(1, $survey->getNotificationsOfType(NotificationType::IN_PROGRESS_REMINDER_1, NotificationChannel::SMS));
    }

    public function testInProgressReminderFallsBackToInvitationEmails(): void
    {
        $this->idsOnPersist();
        $survey = $this->inProgressSurvey(null, null, 'a@example.com, b@example.com');

        self::assertSame([NotificationChannel::EMAIL], $this->sender->send($survey, NotificationType::IN_PROGRESS_REMINDER_2));
        self::assertSame(['a@example.com', 'b@example.com'], array_map(fn(array $d) => $d[0]->getEmailAddress(), $this->dispatched));
        $rows = $survey->getNotificationsOfType(NotificationType::IN_PROGRESS_REMINDER_2, NotificationChannel::EMAIL);
        self::assertCount(1, $rows, 'one row for the channel, however many recipients');
        self::assertSame([$rows[0]->getId(), $rows[0]->getId()], array_map(fn(array $d) => $d[0]->getOriginatingEntityId(), $this->dispatched));
    }

    public function testLandlineGetsEmailOnlyAndUsesNoAllowance(): void
    {
        $this->idsOnPersist();
        $this->smsAllowance->expects(self::never())->method('tryConsume');

        self::assertSame([NotificationChannel::EMAIL], $this->sender->send($this->inProgressSurvey('c@example.com', '01225 123456'), NotificationType::IN_PROGRESS_REMINDER_1));
        self::assertSame([], $this->perNumberSent, 'no per-number unit for a number that is not a UK mobile');
        self::assertSame([NotificationChannel::EMAIL], $this->sender->send($this->inProgressSurvey('c@example.com', '07700 900123 (mobile)'), NotificationType::IN_PROGRESS_REMINDER_1));
        self::assertSame([], $this->perNumberSent, 'nor for one with extra text');
    }

    public function testExhaustedAllowanceStillSendsTheEmail(): void
    {
        $this->idsOnPersist();
        $this->smsAllowance->method('tryConsume')->willReturn(false);
        $this->smsAllowance->expects(self::never())->method('recordReservation');
        $survey = $this->inProgressSurvey('c@example.com', '07700 900123');

        self::assertSame([NotificationChannel::EMAIL], $this->sender->send($survey, NotificationType::IN_PROGRESS_REMINDER_1));
        self::assertSame([], $survey->getNotificationsOfType(NotificationType::IN_PROGRESS_REMINDER_1, NotificationChannel::SMS));
    }

    public function testMobileWithNoUsableEmailGetsSmsOnly(): void
    {
        $this->idsOnPersist();
        $this->smsAllowance->method('tryConsume')->willReturn(true);
        $survey = $this->inProgressSurvey('blocked@example.com', '07700 900123', null);

        self::assertSame([NotificationChannel::SMS], $this->sender->send($survey, NotificationType::IN_PROGRESS_REMINDER_3));
        self::assertSame([], $survey->getNotificationsOfType(NotificationType::IN_PROGRESS_REMINDER_3, NotificationChannel::EMAIL));
    }

    public function testNoEmailAndNoMobileRecordsNothing(): void
    {
        $this->entityManager->expects(self::never())->method('persist');
        self::assertSame([], $this->sender->send($this->inProgressSurvey(null, null, null), NotificationType::IN_PROGRESS_REMINDER_1));
    }

    public function testPreviewAllowingSmsBuildsEveryChannelAndChargesNothing(): void
    {
        $this->entityManager->expects(self::never())->method('persist');
        $this->smsAllowance->expects(self::never())->method('tryConsume');
        $survey = $this->inProgressSurvey('c@example.com', '07700 900123');

        $preview = $this->sender->preview($survey, NotificationType::IN_PROGRESS_REMINDER_1, fn(string $mobile): ?string => null);

        self::assertCount(2, $preview['messages']);
        [$email, $sms] = $preview['messages'];
        self::assertInstanceOf(Email::class, $email);
        self::assertInstanceOf(Sms::class, $sms);
        self::assertSame('07700900123', $sms->getPhoneNumber());
        self::assertSame(['dry-run', 'dry-run'], array_map(fn($m) => $m->getOriginatingEntityId(), $preview['messages']));
        self::assertStringStartsWith(Survey::class.'::survey-ip::', $sms->getReference());
        self::assertSame([], $preview['skipped']);
        self::assertSame([], $this->dispatched);
        self::assertSame([], $this->perNumberSent, 'the real per-number cap is never touched');
    }

    public function testPreviewReportsRecipientlessChannelsWithReasons(): void
    {
        $survey = $this->inProgressSurvey(null, '01225 123456', null);
        $preview = $this->sender->preview($survey, NotificationType::IN_PROGRESS_REMINDER_1, fn(string $mobile): ?string => self::fail('no mobile to ask about'));

        self::assertSame([], $preview['messages']);
        self::assertSame([
            ['channel' => NotificationChannel::EMAIL, 'reason' => 'no email recipients'],
            ['channel' => NotificationChannel::SMS, 'reason' => 'not a UK mobile'],
        ], $preview['skipped']);
    }

    public function testLetterPersonalisationIncludesTheAddressLines(): void
    {
        $survey = (new Survey())->setId('survey-letter')->setState(Survey::STATE_INVITATION_SENT)
            ->setInvitationAddress((new LongAddress())->setLine1('Co')->setLine2('1 Road')->setPostcode('BA1 1HR'));

        $messages = $this->sender->preview($survey, NotificationType::REMINDER_1, fn(string $mobile): ?string => null)['messages'];

        self::assertInstanceOf(Letter::class, $messages[0]);
        self::assertArrayHasKey('address_line_1', $messages[0]->getPersonalisation());
        self::assertSame('Co', $messages[0]->getPersonalisation()['address_line_1']);
        self::assertSame('y', $messages[0]->getPersonalisation()['x'], 'the helper personalisation (mocked as x => y) is kept');
    }

    public function testSendDispatchesMessagesWhoseOriginatingIdIsTheirRow(): void
    {
        $this->idsOnPersist();
        $this->smsAllowance->method('tryConsume')->willReturn(true);
        $survey = $this->inProgressSurvey('contact@example.com', '07700 900123');

        $this->sender->send($survey, NotificationType::IN_PROGRESS_REMINDER_1);

        $rowIds = [
            'email' => $survey->getNotificationsOfType(NotificationType::IN_PROGRESS_REMINDER_1, NotificationChannel::EMAIL)[0]->getId(),
            'sms' => $survey->getNotificationsOfType(NotificationType::IN_PROGRESS_REMINDER_1, NotificationChannel::SMS)[0]->getId(),
        ];
        self::assertSame($rowIds['email'], $this->dispatched[0][0]->getOriginatingEntityId());
        self::assertSame($rowIds['sms'], $this->dispatched[1][0]->getOriginatingEntityId());
    }

    public function testPreviewUsesTheBudgetAndRecordsNothing(): void
    {
        $this->entityManager->expects(self::never())->method('persist');
        $this->smsAllowance->expects(self::never())->method('tryConsume');
        $survey = $this->inProgressSurvey('c@example.com', '07700 900123');
        $asked = [];

        $preview = $this->sender->preview($survey, NotificationType::IN_PROGRESS_REMINDER_1, function (string $mobile) use (&$asked): string {
            $asked[] = $mobile;
            return 'SMS per-number daily cap reached';
        });

        self::assertSame(['07700900123'], $asked, 'the budget is asked about the normalised mobile');
        self::assertCount(1, $preview['messages']);
        self::assertSame([['channel' => NotificationChannel::SMS, 'reason' => 'SMS per-number daily cap reached']], $preview['skipped']);
        self::assertSame('dry-run', $preview['messages'][0]->getOriginatingEntityId());
        self::assertSame([], $this->dispatched);
        self::assertSame([], $this->perNumberSent, 'the real per-number cap is never touched');
    }

    public function testSixSurveysSharingAMobileGetFiveSms(): void
    {
        $this->idsOnPersist();
        $this->perNumberCap = 5;
        $this->smsAllowance->method('tryConsume')->willReturn(true);

        $sent = [];
        foreach (range(1, 6) as $i) {
            $sent[] = $this->sender->send($this->inProgressSurvey("c{$i}@example.com", '07700 900123')->setId("survey-{$i}"), NotificationType::IN_PROGRESS_REMINDER_1);
        }

        self::assertSame(array_fill(0, 5, [NotificationChannel::EMAIL, NotificationChannel::SMS]), array_slice($sent, 0, 5));
        self::assertSame([NotificationChannel::EMAIL], $sent[5], 'the sixth gets its email, and no SMS');
        self::assertContains('Notifications: Skipped in-progress-reminder-1 via sms for survey-6: per-number daily cap reached', $this->infoLogs);
    }

    public function testSixthSurveyWithoutEmailRecordsNothingAndStaysDue(): void
    {
        $this->idsOnPersist();
        $this->perNumberCap = 5;
        $this->smsAllowance->method('tryConsume')->willReturn(true);
        foreach (range(1, 5) as $i) {
            $this->sender->send($this->inProgressSurvey(null, '07700 900123', null)->setId("survey-{$i}"), NotificationType::IN_PROGRESS_REMINDER_1);
        }
        $sixth = $this->inProgressSurvey(null, '07700 900123', null)->setId('survey-6');

        self::assertSame([], $this->sender->send($sixth, NotificationType::IN_PROGRESS_REMINDER_1));
        self::assertSame([], $sixth->getNotificationsOfType(NotificationType::IN_PROGRESS_REMINDER_1));
    }

    public function testPerNumberRefusalLeavesTheAnnualAllowanceAlone(): void
    {
        $this->idsOnPersist();
        $this->perNumberCap = 0;
        $this->smsAllowance->expects(self::never())->method('tryConsume');

        self::assertSame([NotificationChannel::EMAIL], $this->sender->send($this->inProgressSurvey('c@example.com', '07700 900123'), NotificationType::IN_PROGRESS_REMINDER_1));
    }

    public function testAnnualRefusalAfterThePerNumberUnitLeavesThatUnitTaken(): void
    {
        $this->idsOnPersist();
        $this->smsAllowance->method('tryConsume')->willReturn(false);

        $this->sender->send($this->inProgressSurvey('c@example.com', '07700 900123'), NotificationType::IN_PROGRESS_REMINDER_1);

        self::assertSame(['07700900123' => 1], $this->perNumberSent, 'per-number is charged first, and its unit is not given back');
    }

    public function testDifferentlyWrittenNumbersShareACount(): void
    {
        $this->idsOnPersist();
        $this->smsAllowance->method('tryConsume')->willReturn(true);

        $this->sender->send($this->inProgressSurvey('a@example.com', '+44 7700 900123')->setId('survey-a'), NotificationType::IN_PROGRESS_REMINDER_1);
        $this->sender->send($this->inProgressSurvey('b@example.com', '07700900123')->setId('survey-b'), NotificationType::IN_PROGRESS_REMINDER_1);

        self::assertSame(['07700900123' => 2], $this->perNumberSent);
    }
}
