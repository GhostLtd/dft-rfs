<?php

namespace App\Utility\AlphagovNotify\Callback;

use App\Entity\NotifyCallback;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * Stores a GOV.UK Notify callback (delivery receipt or returned letter) against the response it's about.
 *
 * - Callbacks are matched on Notify's notification id (notify_api_response.notify_id); unmatched ones aren't ours
 *   (all environments share one Notify service, so its single callback URL receives every environment's callbacks)
 *   and aren't stored.
 * - Notify may send the same callback more than once, so storing is idempotent (unique on notify id/type/status),
 *   and only a newly stored fatal outcome marks the response's problem as unseen: a repeat leaves an admin's "seen".
 * - The recipient ("to") isn't stored: the response already records it.
 */
class CallbackProcessor
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(
        private readonly Connection      $connection,
        private readonly UuidFactory     $uuidFactory,
        private readonly ClockInterface  $clock,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'default::APP_NOTIFY_REFERENCE_PREFIX')]
        private readonly ?string         $referencePrefix = null,
    ) {}

    /**
     * @throws InvalidCallbackException
     */
    public function process(string $type, mixed $payload): CallbackOutcome
    {
        if (!is_array($payload) || array_is_list($payload)) {
            throw new InvalidCallbackException('Payload is not a JSON object');
        }

        [$notifyId, $status, $occurredAt] = match ($type) {
            NotifyCallback::TYPE_DELIVERY_RECEIPT => [
                $payload['id'] ?? null,
                $payload['status'] ?? null,
                $payload['completed_at'] ?? null,
            ],
            NotifyCallback::TYPE_RETURNED_LETTER => [
                $payload['notification_id'] ?? null,
                NotifyCallback::STATUS_RETURNED,
                $payload['date_sent'] ?? null,
            ],
            default => throw new InvalidCallbackException("Unknown callback type \"{$type}\""),
        };

        if (!is_string($notifyId) || !preg_match(self::UUID_PATTERN, $notifyId)) {
            throw new InvalidCallbackException('Missing or invalid notification id');
        }

        if ($type === NotifyCallback::TYPE_DELIVERY_RECEIPT && !in_array($status, NotifyCallback::DELIVERY_RECEIPT_STATUSES, true)) {
            throw new InvalidCallbackException('Missing or unexpected status');
        }

        $reference = is_string($payload['reference'] ?? null) ? $payload['reference'] : '';
        $responseId = $this->connection->fetchOne(
            'SELECT id FROM notify_api_response WHERE notify_id = ? ORDER BY timestamp DESC',
            [strtolower($notifyId)],
        );

        if ($responseId === false) {
            $this->logUnmatched($type, $status, $notifyId, $reference);
            return CallbackOutcome::UNMATCHED;
        }

        unset($payload['to']);

        return $this->connection->transactional(function (Connection $connection) use ($type, $status, $notifyId, $occurredAt, $payload, $responseId): CallbackOutcome {
            try {
                $connection->insert('notify_callback', [
                    'id' => (string) $this->uuidFactory->create(),
                    'response_id' => $responseId,
                    'notify_id' => strtolower($notifyId),
                    'type' => $type,
                    'status' => $status,
                    'payload' => $payload,
                    'occurred_at' => $this->toLocalDateTime($occurredAt),
                    'received_at' => \DateTime::createFromImmutable($this->clock->now())->setTimezone($this->localTimezone()),
                ], [
                    'payload' => Types::JSON,
                    'occurred_at' => Types::DATETIME_MUTABLE,
                    'received_at' => Types::DATETIME_MUTABLE,
                ]);
            } catch (UniqueConstraintViolationException) {
                return CallbackOutcome::DUPLICATE;
            }

            if (in_array($status, NotifyCallback::FATAL_STATUSES, true)) {
                $connection->executeStatement('UPDATE notify_api_response SET problem_seen_at = NULL WHERE id = ?', [$responseId]);
            }

            return CallbackOutcome::STORED;
        });
    }

    /**
     * Other environments' callbacks are expected here (their references carry a different prefix, or none for
     * originless messages); an unmatched callback from this environment's own sends is not.
     */
    private function logUnmatched(string $type, string $status, string $notifyId, string $reference): void
    {
        // Without the recipient hash on the end: a phone number's hash can be brute-forced
        $context = ['type' => $type, 'status' => $status, 'notifyId' => $notifyId, 'reference' => preg_replace('/::[0-9a-f]{64}$/i', '', $reference)];
        $origin = self::referenceOrigin($reference);

        if ($origin !== null && $origin === ($this->referencePrefix ?? '')) {
            $this->logger->warning('Notify callback for one of our sends matched no response', $context);
        } else {
            $this->logger->info('Notify callback not for this environment; ignored', $context);
        }
    }

    /**
     * The environment prefix a reference was sent with: '' for an unprefixed survey/user reference (as prod sends),
     * the prefix for "{prefix}::…" or a bare "{prefix}", and null when there's no reference to go on.
     */
    public static function referenceOrigin(string $reference): ?string
    {
        if ($reference === '') {
            return null;
        }

        if (str_starts_with($reference, 'App\\')) {
            return '';
        }

        return explode('::', $reference, 2)[0];
    }

    private function toLocalDateTime(mixed $value): ?\DateTime
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return (new \DateTime($value))->setTimezone($this->localTimezone());
        } catch (\Exception) {
            return null;
        }
    }

    private function localTimezone(): \DateTimeZone
    {
        return new \DateTimeZone(date_default_timezone_get());
    }
}
