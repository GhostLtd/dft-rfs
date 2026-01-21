<?php

namespace App\Tests\Utility\AlphagovNotify;

use App\Entity\Embeddable\Address;
use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\PendingNotificationService;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class PendingNotificationServiceTest extends TestCase
{
    private const SURVEY_ID = 'test-survey-uuid-1234';

    private function createMockSurvey(): SurveyInterface
    {
        $survey = $this->createMock(SurveyInterface::class);
        $survey->method('getId')->willReturn(self::SURVEY_ID);
        return $survey;
    }

    private function createSerializedEnvelope(object $message): string
    {
        $envelope = new Envelope($message);
        $serializer = new PhpSerializer();
        $encoded = $serializer->encode($envelope);
        return $encoded['body'];
    }

    private function createLetterMessage(string $eventName): Letter
    {
        $address = new Address();
        $address->setLine1('123 Test Street');
        $address->setLine2('Test Town');
        $address->setPostcode('TE1 1ST');

        return new Letter(
            $eventName,
            'App\Entity\Domestic\Survey',
            self::SURVEY_ID,
            $address,
            'template-id-123',
            ['key' => 'value']
        );
    }

    private function createEmailMessage(string $eventName, string $emailAddress = 'test@example.com'): Email
    {
        return new Email(
            $eventName,
            'App\Entity\Domestic\Survey',
            self::SURVEY_ID,
            $emailAddress,
            'template-id-123',
            ['key' => 'value']
        );
    }

    private function createMockConnection(array $rows): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn($rows);

        return $connection;
    }

    public function testLoadPendingMessagesWithNoResults(): void
    {
        $connection = $this->createMockConnection([]);
        $service = new PendingNotificationService($connection);

        $survey = $this->createMockSurvey();
        $service->loadPendingMessagesFor($survey);

        $this->assertEmpty($service->getPendingInviteLetters());
        $this->assertEmpty($service->getPendingInviteEmails());
        $this->assertEmpty($service->getPendingReminder1Letters());
        $this->assertEmpty($service->getPendingReminder1Emails());
        $this->assertEmpty($service->getPendingReminder2Letters());
        $this->assertEmpty($service->getPendingReminder2Emails());
        $this->assertEmpty($service->getPendingManualReminderEmails());
    }

    public function testLoadPendingInviteLetter(): void
    {
        $availableAt = new \DateTime('2026-01-20 16:00:00');
        $letterMessage = $this->createLetterMessage(Reference::EVENT_INVITE);

        $connection = $this->createMockConnection([
            [
                'body' => $this->createSerializedEnvelope($letterMessage),
                'available_at' => $availableAt->format('Y-m-d H:i:s'),
            ]
        ]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());

        $letters = $service->getPendingInviteLetters();
        $this->assertCount(1, $letters);
        $this->assertEquals($availableAt, $letters[0]['availableAt']);
        $this->assertEmpty($service->getPendingInviteEmails());
    }

    public function testLoadPendingInviteEmail(): void
    {
        $availableAt = new \DateTime('2026-01-20 17:00:00');
        $emailMessage = $this->createEmailMessage(Reference::EVENT_INVITE);

        $connection = $this->createMockConnection([
            [
                'body' => $this->createSerializedEnvelope($emailMessage),
                'available_at' => $availableAt->format('Y-m-d H:i:s'),
            ]
        ]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());

        $this->assertEmpty($service->getPendingInviteLetters());
        $emails = $service->getPendingInviteEmails();
        $this->assertCount(1, $emails);
        $this->assertEquals($availableAt, $emails[0]['availableAt']);
        $this->assertEquals('test@example.com', $emails[0]['recipient']);
    }

    public function testLoadPendingReminder1Letter(): void
    {
        $availableAt = new \DateTime('2026-01-21 10:00:00');
        $letterMessage = $this->createLetterMessage(Reference::EVENT_REMINDER_1);

        $connection = $this->createMockConnection([
            [
                'body' => $this->createSerializedEnvelope($letterMessage),
                'available_at' => $availableAt->format('Y-m-d H:i:s'),
            ]
        ]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());

        $letters = $service->getPendingReminder1Letters();
        $this->assertCount(1, $letters);
        $this->assertEquals($availableAt, $letters[0]['availableAt']);
        $this->assertEmpty($service->getPendingReminder1Emails());
    }

    public function testLoadPendingReminder1Email(): void
    {
        $availableAt = new \DateTime('2026-01-21 11:00:00');
        $emailMessage = $this->createEmailMessage(Reference::EVENT_REMINDER_1);

        $connection = $this->createMockConnection([
            [
                'body' => $this->createSerializedEnvelope($emailMessage),
                'available_at' => $availableAt->format('Y-m-d H:i:s'),
            ]
        ]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());

        $this->assertEmpty($service->getPendingReminder1Letters());
        $emails = $service->getPendingReminder1Emails();
        $this->assertCount(1, $emails);
        $this->assertEquals($availableAt, $emails[0]['availableAt']);
        $this->assertEquals('test@example.com', $emails[0]['recipient']);
    }

    public function testLoadPendingReminder2Letter(): void
    {
        $availableAt = new \DateTime('2026-01-22 10:00:00');
        $letterMessage = $this->createLetterMessage(Reference::EVENT_REMINDER_2);

        $connection = $this->createMockConnection([
            [
                'body' => $this->createSerializedEnvelope($letterMessage),
                'available_at' => $availableAt->format('Y-m-d H:i:s'),
            ]
        ]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());

        $letters = $service->getPendingReminder2Letters();
        $this->assertCount(1, $letters);
        $this->assertEquals($availableAt, $letters[0]['availableAt']);
        $this->assertEmpty($service->getPendingReminder2Emails());
    }

    public function testLoadPendingReminder2Email(): void
    {
        $availableAt = new \DateTime('2026-01-22 11:00:00');
        $emailMessage = $this->createEmailMessage(Reference::EVENT_REMINDER_2);

        $connection = $this->createMockConnection([
            [
                'body' => $this->createSerializedEnvelope($emailMessage),
                'available_at' => $availableAt->format('Y-m-d H:i:s'),
            ]
        ]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());

        $this->assertEmpty($service->getPendingReminder2Letters());
        $emails = $service->getPendingReminder2Emails();
        $this->assertCount(1, $emails);
        $this->assertEquals($availableAt, $emails[0]['availableAt']);
        $this->assertEquals('test@example.com', $emails[0]['recipient']);
    }

    public function testLoadPendingManualReminderEmails(): void
    {
        $availableAt1 = new \DateTime('2026-01-23 09:00:00');
        $availableAt2 = new \DateTime('2026-01-23 10:00:00');

        $emailMessage1 = $this->createEmailMessage(Reference::EVENT_MANUAL_REMINDER, 'test1@example.com');
        $emailMessage2 = $this->createEmailMessage(Reference::EVENT_MANUAL_REMINDER, 'test2@example.com');

        $connection = $this->createMockConnection([
            [
                'body' => $this->createSerializedEnvelope($emailMessage1),
                'available_at' => $availableAt1->format('Y-m-d H:i:s'),
            ],
            [
                'body' => $this->createSerializedEnvelope($emailMessage2),
                'available_at' => $availableAt2->format('Y-m-d H:i:s'),
            ],
        ]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());

        $manualReminders = $service->getPendingManualReminderEmails();
        $this->assertCount(2, $manualReminders);
        $this->assertEquals($availableAt1, $manualReminders[0]['availableAt']);
        $this->assertEquals('test1@example.com', $manualReminders[0]['recipient']);
        $this->assertEquals($availableAt2, $manualReminders[1]['availableAt']);
        $this->assertEquals('test2@example.com', $manualReminders[1]['recipient']);
    }

    public function testMultiplePendingEmailsForSameEvent(): void
    {
        $availableAt = new \DateTime('2026-01-20 14:00:00');

        $connection = $this->createMockConnection([
            [
                'body' => $this->createSerializedEnvelope($this->createEmailMessage(Reference::EVENT_INVITE, 'user1@example.com')),
                'available_at' => $availableAt->format('Y-m-d H:i:s'),
            ],
            [
                'body' => $this->createSerializedEnvelope($this->createEmailMessage(Reference::EVENT_INVITE, 'user2@example.com')),
                'available_at' => $availableAt->format('Y-m-d H:i:s'),
            ],
            [
                'body' => $this->createSerializedEnvelope($this->createEmailMessage(Reference::EVENT_INVITE, 'user3@example.com')),
                'available_at' => $availableAt->format('Y-m-d H:i:s'),
            ],
        ]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());

        $emails = $service->getPendingInviteEmails();
        $this->assertCount(3, $emails);
        $this->assertEquals('user1@example.com', $emails[0]['recipient']);
        $this->assertEquals('user2@example.com', $emails[1]['recipient']);
        $this->assertEquals('user3@example.com', $emails[2]['recipient']);
    }

    public function testMultiplePendingMessages(): void
    {
        $inviteLetterTime = new \DateTime('2026-01-20 14:00:00');
        $inviteEmailTime = new \DateTime('2026-01-20 14:00:00');
        $reminder1LetterTime = new \DateTime('2026-01-27 14:00:00');

        $connection = $this->createMockConnection([
            [
                'body' => $this->createSerializedEnvelope($this->createLetterMessage(Reference::EVENT_INVITE)),
                'available_at' => $inviteLetterTime->format('Y-m-d H:i:s'),
            ],
            [
                'body' => $this->createSerializedEnvelope($this->createEmailMessage(Reference::EVENT_INVITE)),
                'available_at' => $inviteEmailTime->format('Y-m-d H:i:s'),
            ],
            [
                'body' => $this->createSerializedEnvelope($this->createLetterMessage(Reference::EVENT_REMINDER_1)),
                'available_at' => $reminder1LetterTime->format('Y-m-d H:i:s'),
            ],
        ]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());

        $inviteLetters = $service->getPendingInviteLetters();
        $inviteEmails = $service->getPendingInviteEmails();
        $reminder1Letters = $service->getPendingReminder1Letters();

        $this->assertCount(1, $inviteLetters);
        $this->assertEquals($inviteLetterTime, $inviteLetters[0]['availableAt']);

        $this->assertCount(1, $inviteEmails);
        $this->assertEquals($inviteEmailTime, $inviteEmails[0]['availableAt']);

        $this->assertCount(1, $reminder1Letters);
        $this->assertEquals($reminder1LetterTime, $reminder1Letters[0]['availableAt']);

        $this->assertEmpty($service->getPendingReminder1Emails());
        $this->assertEmpty($service->getPendingReminder2Letters());
        $this->assertEmpty($service->getPendingReminder2Emails());
    }

    public function testQueryFiltersBySurveyId(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                $this->stringContains('queue_name'),
                $this->callback(function ($params) {
                    return isset($params['surveyId']) && str_contains($params['surveyId'], self::SURVEY_ID);
                })
            )
            ->willReturn([]);

        $service = new PendingNotificationService($connection);
        $service->loadPendingMessagesFor($this->createMockSurvey());
    }
}
