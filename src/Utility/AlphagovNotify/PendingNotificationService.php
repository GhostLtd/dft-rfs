<?php

namespace App\Utility\AlphagovNotify;

use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class PendingNotificationService
{
    /** @var array<string, array<array{availableAt: \DateTime}>> */
    private array $pendingLetters = [];

    /** @var array<string, array<array{availableAt: \DateTime, recipient: string}>> */
    private array $pendingEmails = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function loadPendingMessagesFor(SurveyInterface $survey): self
    {
        $this->pendingLetters = [];
        $this->pendingEmails = [];

        $rows = $this->connection->fetchAllAssociative(
            'SELECT body, available_at FROM messenger_messages WHERE queue_name = :queueName AND body LIKE :surveyId',
            [
                'queueName' => 'govuk-notify',
                'surveyId' => '%' . $survey->getId() . '%',
            ]
        );

        $serializer = new PhpSerializer();

        foreach ($rows as $row) {
            try {
                $envelope = $serializer->decode(['body' => $row['body']]);
                $message = $envelope->getMessage();

                if (!$message instanceof Letter && !$message instanceof Email) {
                    continue;
                }

                $eventName = $message->getEventName();
                /** @var string $availableAtStr */
                $availableAtStr = $row['available_at'];
                $availableAt = new \DateTime($availableAtStr);

                if ($message instanceof Letter) {
                    $this->pendingLetters[$eventName][] = [
                        'availableAt' => $availableAt,
                    ];
                } elseif ($message instanceof Email) {
                    $this->pendingEmails[$eventName][] = [
                        'availableAt' => $availableAt,
                        'recipient' => $message->getEmailAddress(),
                    ];
                }
            } catch (\Throwable) {
                // Skip messages that can't be decoded
                continue;
            }
        }

        return $this;
    }

    /**
     * @return array<array{availableAt: \DateTime}>
     */
    public function getPendingInviteLetters(): array
    {
        return $this->pendingLetters[Reference::EVENT_INVITE] ?? [];
    }

    /**
     * @return array<array{availableAt: \DateTime, recipient: string}>
     */
    public function getPendingInviteEmails(): array
    {
        return $this->pendingEmails[Reference::EVENT_INVITE] ?? [];
    }

    /**
     * @return array<array{availableAt: \DateTime}>
     */
    public function getPendingReminder1Letters(): array
    {
        return $this->pendingLetters[Reference::EVENT_REMINDER_1] ?? [];
    }

    /**
     * @return array<array{availableAt: \DateTime, recipient: string}>
     */
    public function getPendingReminder1Emails(): array
    {
        return $this->pendingEmails[Reference::EVENT_REMINDER_1] ?? [];
    }

    /**
     * @return array<array{availableAt: \DateTime}>
     */
    public function getPendingReminder2Letters(): array
    {
        return $this->pendingLetters[Reference::EVENT_REMINDER_2] ?? [];
    }

    /**
     * @return array<array{availableAt: \DateTime, recipient: string}>
     */
    public function getPendingReminder2Emails(): array
    {
        return $this->pendingEmails[Reference::EVENT_REMINDER_2] ?? [];
    }

    /**
     * @return array<array{availableAt: \DateTime, recipient: string}>
     */
    public function getPendingManualReminderEmails(): array
    {
        return $this->pendingEmails[Reference::EVENT_MANUAL_REMINDER] ?? [];
    }
}
