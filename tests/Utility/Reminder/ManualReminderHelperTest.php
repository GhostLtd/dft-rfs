<?php

namespace App\Tests\Utility\Reminder;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyNote;
use App\Entity\Domestic\SurveyResponse as DomesticResponse;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\International\SurveyResponse as InternationalResponse;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Entity\SurveyManualReminderInterface;
use App\Entity\SurveyStateInterface;
use App\Repository\AuditLog\AuditLogRepository;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AuditEntityLogger\SurveyStateLogger;
use App\Utility\EmailBlacklistService;
use App\Utility\NotificationInterceptionService;
use App\Utility\Reminder\ManualReminderHelper;
use App\Utility\Reminder\UnavailabilityReason;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

class ManualReminderHelperTest extends KernelTestCase
{
    protected AuditLogRepository&MockObject $auditLogRepositoryMock;
    protected EmailBlacklistService&MockObject $emailBlacklistServiceMock;

    protected ManualReminderHelper $manualReminderHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->auditLogRepositoryMock = $this->createMock(AuditLogRepository::class);
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $messageBusMock = $this->createMock(MessageBusInterface::class);
        $personalisationHelperMock = $this->createMock(PersonalisationHelper::class);
        $securityMock = $this->createMock(Security::class);
        $notificationInterceptionServiceMock = $this->createMock(NotificationInterceptionService::class);
        $notificationInterceptionServiceMock
            ->method('getAllEmailsForSurvey')
            ->willReturnCallback(fn(SurveyManualReminderInterface $s) => $s->getArrayOfInvitationEmails());

        $this->emailBlacklistServiceMock = $this->createMock(EmailBlacklistService::class);
        $this->emailBlacklistServiceMock
            ->method('filterBlacklistedEmails')
            ->willReturnCallback(fn(array $emails) => $emails); // Pass-through by default

        $this->manualReminderHelper = new ManualReminderHelper(
            $this->auditLogRepositoryMock,
            $entityManagerMock,
            $messageBusMock,
            $personalisationHelperMock,
            $securityMock,
            $notificationInterceptionServiceMock,
            $this->emailBlacklistServiceMock,
        );
    }

    public static function dataCanShowManualReminderButton(): array
    {
        $domesticSurvey = fn(string $state): DomesticSurvey => (new DomesticSurvey())->setState($state);
        $internationalSurvey = fn(string $state): InternationalSurvey => (new InternationalSurvey())->setState($state);
        $preEnquiry = fn(string $state): PreEnquiry => (new PreEnquiry())->setState($state);

        $testCases = [];

        foreach(DomesticSurvey::STATE_FILTER_CHOICES as $state) {
            $testCases[] = [$domesticSurvey($state), $state === SurveyStateInterface::STATE_IN_PROGRESS];
        }

        foreach(InternationalSurvey::STATE_FILTER_CHOICES as $state) {
            $testCases[] = [$internationalSurvey($state), $state === SurveyStateInterface::STATE_IN_PROGRESS];
        }

        foreach(PreEnquiry::STATE_FILTER_CHOICES as $state) {
            $testCases[] = [$preEnquiry($state), false]; // Not yet supported
        }

        return $testCases;
    }

    #[DataProvider('dataCanShowManualReminderButton')]
    public function testCanShowManualReminderButton(SurveyManualReminderInterface $survey, bool $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->manualReminderHelper->canShowManualReminderButton($survey));
    }

    public static function dataGetRecipientEmails(): array
    {
        $domesticSurvey = fn(?string $contactEmail, ?string $invitationEmails): DomesticSurvey =>
            (new DomesticSurvey())
                ->setInvitationEmails($invitationEmails)
                ->setResponse(
                    (new DomesticResponse())
                        ->setContactEmail($contactEmail)
                );

        $internationalSurvey = fn(?string $contactEmail, ?string $invitationEmails): InternationalSurvey =>
            (new InternationalSurvey())
                ->setInvitationEmails($invitationEmails)
                ->setResponse(
                    (new InternationalResponse())
                        ->setContactEmail($contactEmail)
                );

        $preEnquiry = fn(?string $contactEmail, ?string $invitationEmails): PreEnquiry =>
            (new PreEnquiry())
                ->setInvitationEmails($invitationEmails)
                ->setResponse(
                    (new PreEnquiryResponse())
                        ->setContactEmail($contactEmail)
                );

        $testCases = [];

        // All three survey-types should work identically...
        foreach([$domesticSurvey, $internationalSurvey, $preEnquiry] as $surveyGenerator) {
            $testCases[] = [$surveyGenerator(null, null), null];
            $testCases[] = [$surveyGenerator('one@example.com', null), ['one@example.com']];
            $testCases[] = [$surveyGenerator('one@example.com', 'two@example.com'), ['one@example.com']];
            $testCases[] = [$surveyGenerator(null, 'two@example.com'), ['two@example.com']];
            $testCases[] = [$surveyGenerator(null, '     two@example.com     '), ['two@example.com']];
            $testCases[] = [$surveyGenerator(null, 'two@example.com,three@example.com'), ['two@example.com', 'three@example.com']];
            $testCases[] = [$surveyGenerator(null, 'two@example.com,,,,three@example.com'), ['two@example.com', 'three@example.com']];
            $testCases[] = [$surveyGenerator(null, '     two@example.com     ,     three@example.com     '), ['two@example.com', 'three@example.com']];
            $testCases[] = [$surveyGenerator(null, 'two@example.com,two@example.com'), ['two@example.com']];
            $testCases[] = [$surveyGenerator(null, '     two@example.com     ,     two@example.com     '), ['two@example.com']];
            $testCases[] = [$surveyGenerator(null, ''), null];
            $testCases[] = [$surveyGenerator(null, ',     ,,'), null];
        }

        return $testCases;
    }

    #[DataProvider('dataGetRecipientEmails')]
    public function testGetRecipientEmails(SurveyManualReminderInterface $survey, ?array $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->manualReminderHelper->getRecipientEmails($survey));
    }

    public static function dataGetInProgressDate(): array
    {
        $auditLog = fn(\DateTime $timestamp, array $data = [], string $category = SurveyStateLogger::CATEGORY) =>
            (new AuditLog())->setTimestamp($timestamp)->setCategory($category)->setData($data);

        $inProgressDate = new \DateTime('2021-05-06');

        $toRejected = $auditLog(new \DateTime('2021-03-02'), ['to' => SurveyStateInterface::STATE_REJECTED]);
        $toInProgress = $auditLog($inProgressDate, ['to' => SurveyStateInterface::STATE_IN_PROGRESS]);
        $badToInProgress = $auditLog(new \DateTime('2021-05-06'), ['to' => SurveyStateInterface::STATE_IN_PROGRESS], 'other-category');

        return [
            [
                [$toInProgress],
                $inProgressDate
            ], [
                [$toRejected, $toInProgress],
                $inProgressDate
            ], [
                [$toRejected],
                null
            ], [
                [$badToInProgress],
                null
            ],
        ];
    }

    #[DataProvider('dataGetInProgressDate')]
    public function testGetInProgressDate(array $logResults, ?\DateTime $expectedResult): void
    {
        $this->auditLogRepositoryMock
            ->method('getLogs')
            ->willReturn($logResults);

        $reflClass = new \ReflectionClass($this->manualReminderHelper);
        $method = $reflClass->getMethod('getInProgressDate');

        $survey = (new Survey())->setId('abc-123');

        $result = $method->invoke($this->manualReminderHelper, $survey);

        $this->assertEquals($expectedResult, $result);
    }

    public static function dataGetReasonWhyCannotSendManualReminder(): array
    {
        $domesticSurvey = fn(string $state, ?string $contactEmail = null, ?string $invitationEmails = null, ?\DateTime $latestManualReminderDate = null): DomesticSurvey =>
        (new DomesticSurvey())
            ->setResponse(
                (new DomesticResponse())
                    ->setContactEmail($contactEmail)
            )
            ->setId('abc-123')
            ->setState($state)
            ->setInvitationSentDate(null)
            ->setFirstReminderSentDate(null)
            ->setSecondReminderSentDate(null)
            ->setLatestManualReminderSentDate($latestManualReminderDate)
            ->setInvitationEmails($invitationEmails)
;

        $internationalSurvey = fn(string $state, ?string $contactEmail = null, ?string $invitationEmails = null, ?\DateTime $latestManualReminderDate = null): InternationalSurvey =>
        (new InternationalSurvey())
            ->setResponse(
                (new InternationalResponse())
                    ->setContactEmail($contactEmail)
            )            ->setId('abc-123')
            ->setState($state)
            ->setInvitationSentDate(null)
            ->setFirstReminderSentDate(null)
            ->setSecondReminderSentDate(null)
            ->setLatestManualReminderSentDate($latestManualReminderDate)
            ->setInvitationEmails($invitationEmails)
            ;

        $preEnquiry = fn(string $state, ?string $contactEmail = null, ?string $invitationEmails = null, ?\DateTime $latestManualReminderDate = null): PreEnquiry =>
        (new PreEnquiry())
            ->setResponse(
                (new PreEnquiryResponse())
                    ->setContactEmail($contactEmail)
            )
            ->setId('abc-123')
            ->setState($state)
            ->setInvitationSentDate(null)
            ->setFirstReminderSentDate(null)
            ->setSecondReminderSentDate(null)
            ->setLatestManualReminderSentDate($latestManualReminderDate)
            ->setInvitationEmails($invitationEmails)
            ;

        $sixDaysAgo = (new \DateTime())->modify('-6 days');
        $eightDaysAgo = (new \DateTime())->modify('-8 days');

        return [
            [$domesticSurvey(SurveyStateInterface::STATE_NEW), UnavailabilityReason::NOT_IN_PROGRESS],
            [$internationalSurvey(SurveyStateInterface::STATE_NEW), UnavailabilityReason::NOT_IN_PROGRESS],
            [$preEnquiry(SurveyStateInterface::STATE_NEW), UnavailabilityReason::NO_NOTIFY_TEMPLATE], // Currently not supported

            // -----

            [$domesticSurvey(SurveyStateInterface::STATE_IN_PROGRESS), UnavailabilityReason::NO_EMAIL_ADDRESSES],

            [$domesticSurvey(SurveyStateInterface::STATE_IN_PROGRESS, 'one@example.com', null, $sixDaysAgo), UnavailabilityReason::TOO_SOON],
            [$domesticSurvey(SurveyStateInterface::STATE_IN_PROGRESS, 'one@example.com', 'two@example.com,three@example.com', $sixDaysAgo), UnavailabilityReason::TOO_SOON],
            [$domesticSurvey(SurveyStateInterface::STATE_IN_PROGRESS, null, 'two@example.com,three@example.com', $sixDaysAgo), UnavailabilityReason::TOO_SOON],

            [$domesticSurvey(SurveyStateInterface::STATE_IN_PROGRESS, 'one@example.com', null, $eightDaysAgo), null],
            [$domesticSurvey(SurveyStateInterface::STATE_IN_PROGRESS, 'one@example.com', 'two@example.com,three@example.com', $eightDaysAgo), null],
            [$domesticSurvey(SurveyStateInterface::STATE_IN_PROGRESS, null, 'two@example.com,three@example.com', $eightDaysAgo), null],

            // -----

            [$internationalSurvey(SurveyStateInterface::STATE_IN_PROGRESS), UnavailabilityReason::NO_EMAIL_ADDRESSES],

            [$internationalSurvey(SurveyStateInterface::STATE_IN_PROGRESS, 'one@example.com', null, $sixDaysAgo), UnavailabilityReason::TOO_SOON],
            [$internationalSurvey(SurveyStateInterface::STATE_IN_PROGRESS, 'one@example.com', 'two@example.com,three@example.com', $sixDaysAgo), UnavailabilityReason::TOO_SOON],
            [$internationalSurvey(SurveyStateInterface::STATE_IN_PROGRESS, null, 'two@example.com,three@example.com', $sixDaysAgo), UnavailabilityReason::TOO_SOON],

            [$internationalSurvey(SurveyStateInterface::STATE_IN_PROGRESS, 'one@example.com', null, $eightDaysAgo), null],
            [$internationalSurvey(SurveyStateInterface::STATE_IN_PROGRESS, 'one@example.com', 'two@example.com,three@example.com', $eightDaysAgo), null],
            [$internationalSurvey(SurveyStateInterface::STATE_IN_PROGRESS, null, 'two@example.com,three@example.com', $eightDaysAgo), null],

        ];
    }

    #[DataProvider('dataGetReasonWhyCannotSendManualReminder')]
    public function testGetReasonWhyCannotSendManualReminder(SurveyManualReminderInterface $survey, ?string $expectedResult): void
    {
        $this->auditLogRepositoryMock
            ->method('getLogs')
            ->willReturn([]);

        $reason = $this->manualReminderHelper->getReasonWhyCannotSendManualReminder($survey);

        if ($expectedResult === null) {
            $this->assertNull($reason);
        } else {
            $this->assertNotNull($reason);
            $this->assertEquals($expectedResult, $reason->getReason());
        }
    }

    public static function dataGetMostRecentEventDate(): array
    {
        $one = new \DateTime('2021-01-01');
        $two = new \DateTime('2021-03-01');
        $three = new \DateTime('2021-05-01');
        $four = new \DateTime('2021-07-01');
        $five = new \DateTime('2021-08-01');

        $getSurvey = function(
            ?\DateTime $invitationDate,
            ?\DateTime $firstReminderDate,
            ?\DateTime $secondReminderDate,
            ?\DateTime $manualReminderDate,
            ?\DateTime $chaseNoteDate,
        ) {
            $survey = (new DomesticSurvey())
                ->setId('abc-123')
                ->setInvitationSentDate($invitationDate)
                ->setFirstReminderSentDate($firstReminderDate)
                ->setSecondReminderSentDate($secondReminderDate)
                ->setLatestManualReminderSentDate($manualReminderDate);

            $addNote = function(\DateTime $date, string $message) use ($survey) {
                $note = (new SurveyNote())
                    ->setNote('Manual reminder')
                    ->setWasChased(true)
                    ->setCreatedAt($date);

                $survey->addNote($note);
            };

            if ($manualReminderDate) {
                $addNote($manualReminderDate, 'Manual reminder');
            }

            if ($chaseNoteDate) {
                $addNote($chaseNoteDate, 'Contacted by phone');
            }

            return $survey;
        };

        $logs = fn(\DateTime $inProgressDate) =>
            [
                (new AuditLog())
                ->setTimestamp($inProgressDate)
                ->setData(['to' => SurveyStateInterface::STATE_IN_PROGRESS])
                ->setCategory(SurveyStateLogger::CATEGORY)
            ];

        return [
            // N.B. Invitation date / firstReminder / secondReminder are ignored by manualHelperReminder, as they
            //      pertain to automated reminders/events that are dispatched on not-yet-started surveys. Manual
            //      reminders can only be sent when the survey is in-progress (hence it only examines in_progress
            //      state change, the last manual reminder send date etc).
            [$getSurvey(null, null, null, null, null), [], null, null],
            [$getSurvey($one, $two, $three, $four, null), $logs($five), $five, 'in_progress'],
            [$getSurvey($one, $two, $three, $five, null), $logs($four), $five, 'manual_reminder'],
            [$getSurvey($one, $two, $five, $three, null), $logs($four), $four, 'in_progress'],
            [$getSurvey($one, $five, $two, $three, null), $logs($four), $four, 'in_progress'],
            [$getSurvey($three, $one, $two, $five, null), $logs($four), $five, 'manual_reminder'],
            [$getSurvey($one, null, $two, $three, null), [], $three, 'manual_reminder'],

            [$getSurvey($one, $two, $five, $three, $five), $logs($four), $five, 'was_chased'],
            [$getSurvey($one, $two, $five, $five, $four), $logs($four), $five, 'manual_reminder'],
        ];
    }

    #[DataProvider('dataGetMostRecentEventDate')]
    public function testGetMostRecentEventDate(SurveyManualReminderInterface $survey, array $logs, ?\DateTime $expectedDate, ?string $expectedEventType): void
    {
        $this->auditLogRepositoryMock
            ->method('getLogs')
            ->willReturn($logs);

        [$latestEventDate, $eventType] = $this->manualReminderHelper->getMostRecentEventDate($survey);

        $this->assertEquals($expectedDate, $latestEventDate);
        $this->assertEquals($expectedEventType, $eventType);
    }

    public function testGetRecipientEmailsReturnsBlacklistedEmail(): void
    {
        $survey = (new DomesticSurvey())
            ->setInvitationEmails(null)
            ->setResponse(
                (new DomesticResponse())
                    ->setContactEmail('blacklisted@example.com')
            );

        $this->assertEquals(['blacklisted@example.com'], $this->manualReminderHelper->getRecipientEmails($survey));
    }

    public function testGetRecipientEmailsReturnsBlacklistedInvitationEmails(): void
    {
        $survey = (new DomesticSurvey())
            ->setInvitationEmails('blacklisted@example.com')
            ->setResponse(
                (new DomesticResponse())
                    ->setContactEmail(null)
            );

        $this->assertEquals(['blacklisted@example.com'], $this->manualReminderHelper->getRecipientEmails($survey));
    }

    public function testSendManualReminderDoesNotDispatchToBlacklistedEmail(): void
    {
        $messageBusMock = $this->createMock(MessageBusInterface::class);
        $messageBusMock
            ->expects($this->never())
            ->method('dispatch');

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects($this->never())
            ->method('persist');
        $entityManagerMock
            ->expects($this->never())
            ->method('flush');

        $emailBlacklistServiceMock = $this->createMock(EmailBlacklistService::class);
        $emailBlacklistServiceMock
            ->method('filterBlacklistedEmails')
            ->willReturn([]);

        $auditLogRepositoryMock = $this->createMock(AuditLogRepository::class);
        $auditLogRepositoryMock
            ->method('getLogs')
            ->willReturn([]);

        $notificationInterceptionServiceMock = $this->createMock(NotificationInterceptionService::class);
        $notificationInterceptionServiceMock
            ->method('getAllEmailsForSurvey')
            ->willReturnCallback(fn(SurveyManualReminderInterface $s) => $s->getArrayOfInvitationEmails());

        $helper = new ManualReminderHelper(
            $auditLogRepositoryMock,
            $entityManagerMock,
            $messageBusMock,
            $this->createMock(PersonalisationHelper::class),
            $this->createMock(Security::class),
            $notificationInterceptionServiceMock,
            $emailBlacklistServiceMock,
        );

        $survey = (new DomesticSurvey())
            ->setId('abc-123')
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS)
            ->setInvitationEmails(null)
            ->setInvitationSentDate(null)
            ->setFirstReminderSentDate(null)
            ->setSecondReminderSentDate(null)
            ->setLatestManualReminderSentDate((new \DateTime())->modify('-8 days'))
            ->setResponse(
                (new DomesticResponse())
                    ->setContactEmail('blacklisted@example.com')
            );

        $result = $helper->sendManualReminder($survey);

        $this->assertFalse($result);
    }

    public function testSendManualReminderReturnsTrueOnSuccess(): void
    {
        $messageBusMock = $this->createMock(MessageBusInterface::class);
        $messageBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new \Symfony\Component\Messenger\Envelope(new \stdClass()));

        $entityManagerMock = $this->createStub(EntityManagerInterface::class);

        $securityMock = $this->createMock(Security::class);
        $userMock = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $userMock->method('getUserIdentifier')->willReturn('test-user');
        $securityMock->method('getUser')->willReturn($userMock);

        $emailBlacklistServiceMock = $this->createMock(EmailBlacklistService::class);
        $emailBlacklistServiceMock
            ->method('filterBlacklistedEmails')
            ->willReturnCallback(fn(array $emails) => $emails);

        $auditLogRepositoryMock = $this->createMock(AuditLogRepository::class);
        $auditLogRepositoryMock
            ->method('getLogs')
            ->willReturn([]);

        $notificationInterceptionServiceMock = $this->createMock(NotificationInterceptionService::class);
        $notificationInterceptionServiceMock
            ->method('getAllEmailsForSurvey')
            ->willReturnCallback(fn(SurveyManualReminderInterface $s) => $s->getArrayOfInvitationEmails());

        $helper = new ManualReminderHelper(
            $auditLogRepositoryMock,
            $entityManagerMock,
            $messageBusMock,
            $this->createMock(PersonalisationHelper::class),
            $securityMock,
            $notificationInterceptionServiceMock,
            $emailBlacklistServiceMock,
        );

        $survey = (new DomesticSurvey())
            ->setId('abc-123')
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS)
            ->setInvitationEmails(null)
            ->setInvitationSentDate(null)
            ->setFirstReminderSentDate(null)
            ->setSecondReminderSentDate(null)
            ->setLatestManualReminderSentDate((new \DateTime())->modify('-8 days'))
            ->setResponse(
                (new DomesticResponse())
                    ->setContactEmail('valid@example.com')
            );

        $result = $helper->sendManualReminder($survey);

        $this->assertTrue($result);
    }
}
