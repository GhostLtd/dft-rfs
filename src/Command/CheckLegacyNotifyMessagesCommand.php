<?php

namespace App\Command;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRoUser;
use App\Messenger\AlphagovNotify\RoRoLoginEmail;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

/**
 * Before the notifications revamp, Notify messages named a survey as their originating entity; the revamped
 * response subscriber expects a notification row (or a RoRoUser), so such messages must have gone before
 * deploying it. See docs/features/2026-09-notifications-revamp.md#deployment.
 *
 * Read-only by construction: messenger_messages is read with plain SQL, because Messenger's own receivers
 * delete any message they fail to decode. Failed messages are never reprocessed, so they are reported
 * separately and never affect the result.
 */
#[AsCommand('app:notifications:check-legacy-messages', 'Check whether any queued Notify message still names a survey as its origin (i.e. whether it is safe to deploy the notifications revamp)')]
class CheckLegacyNotifyMessagesCommand extends Command
{
    public const string LEGACY = 'legacy';
    public const string CURRENT = 'current';
    public const string UNRECOGNISED = 'unrecognised';
    public const string UNREADABLE = 'unreadable';

    private const array CLASSIFICATIONS = [self::LEGACY, self::CURRENT, self::UNRECOGNISED, self::UNREADABLE];

    private const string FAILED_QUEUE = 'failed';

    private const array LEGACY_ORIGINS = [DomesticSurvey::class, InternationalSurvey::class, PreEnquiry::class];

    private const array NOTIFY_MESSAGES = [Email::class, Letter::class, Sms::class];

    // Message classes from before the 2026-05 bundle migration; they no longer exist, but are still in the failed queue
    private const array PRE_BUNDLE_MESSAGES = ['App\Messenger\AlphagovNotify\Email', 'App\Messenger\AlphagovNotify\Letter'];

    // Messages without an originating entity, which the response subscriber ignores
    private const array ORIGINLESS_MESSAGES = [RoRoLoginEmail::class];

    /**
     * The retry_strategy of each queue's transport (config/packages/messenger.yaml; "default" has none, so it has
     * the framework defaults), mirrored because Symfony's strategy keeps them private. Pinned by a test.
     */
    public const array RETRY_POLICIES = [
        'govuk-notify' => ['max_retries' => 3, 'delay' => 1800000, 'multiplier' => 2, 'max_delay' => 7200000, 'jitter' => 0.1],
        'govuk-notify-high-prio' => ['max_retries' => 3, 'delay' => 1800000, 'multiplier' => 2, 'max_delay' => 7200000, 'jitter' => 0.1],
        'default' => ['max_retries' => 3, 'delay' => 1000, 'multiplier' => 2, 'max_delay' => 0, 'jitter' => 0.1],
    ];

    private const string ENVELOPE_MESSAGE = "\0" . Envelope::class . "\0message";
    private const string ENVELOPE_STAMPS = "\0" . Envelope::class . "\0stamps";
    private const string RETRY_COUNT = "\0" . RedeliveryStamp::class . "\0retryCount";
    private const string ORIGIN = "\0*\0originatingEntityClass";
    private const string EVENT = "\0*\0eventName";
    private const string CLASS_NAME = '__PHP_Incomplete_Class_Name';

    public function __construct(
        private readonly Connection     $connection,
        private readonly ClockInterface $clock,
        private readonly int            $pageSize = 200,
    )
    {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = $this->clock->now()->setTimezone(new \DateTimeZone('UTC'));

        $tallies = ['outstanding' => $this->emptyTally(), 'failed' => $this->emptyTally()];
        $legacy = ['delayed' => 0, 'delivered' => 0, 'latestAvailable' => null, 'clearByMs' => null, 'unestimatedQueues' => []];
        $blockingIds = [];
        $failedCreated = [null, null];

        foreach ($this->rows() as $row) {
            [$classification, $messageClass, $origin, $event, $retries] = $this->classify($row['body']);
            $isFailed = $row['queue_name'] === self::FAILED_QUEUE;
            $bucket = $isFailed ? 'failed' : 'outstanding';

            $tallies[$bucket]['counts'][$classification]++;
            $key = join("\0", [$row['queue_name'], $classification, $messageClass, $origin, $event]);
            $tallies[$bucket]['rows'][$key] = ($tallies[$bucket]['rows'][$key] ?? 0) + 1;

            if ($isFailed) {
                $failedCreated = [min($failedCreated[0] ?? $row['created_at'], $row['created_at']), max($failedCreated[1] ?? $row['created_at'], $row['created_at'])];
                continue;
            }

            if ($classification === self::LEGACY) {
                $availableAt = new \DateTimeImmutable($row['available_at'], new \DateTimeZone('UTC'));
                $availableAt > $now && $legacy['delayed']++;
                $row['delivered_at'] !== null && $legacy['delivered']++;
                $legacy['latestAvailable'] = max($legacy['latestAvailable'] ?? $availableAt, $availableAt);

                // Worst case: every remaining attempt fails, each after the longest possible retry wait
                $policy = self::RETRY_POLICIES[$row['queue_name']] ?? null;
                if ($policy === null) {
                    $legacy['unestimatedQueues'][$row['queue_name']] = true;
                } else {
                    $clearByMs = (int) max($availableAt, $now)->format('Uv') + $this->longestRemainingWaitMs($policy, $retries);
                    $legacy['clearByMs'] = max($legacy['clearByMs'] ?? $clearByMs, $clearByMs);
                }
            } else if ($classification !== self::CURRENT) {
                $blockingIds[] = $row['id'];
            }
        }

        $io->section('Outstanding messages (every queue except "failed")');
        $this->report($io, 'Outstanding', $tallies['outstanding']);

        if ($tallies['outstanding']['counts'][self::LEGACY] > 0) {
            $io->text(sprintf('Outstanding legacy messages: %d delayed, %d marked delivered', $legacy['delayed'], $legacy['delivered']));

            // Best case: each is sent at its first attempt, as soon as it's eligible
            $lastEligible = $legacy['latestAvailable']->format('Y-m-d H:i:s');
            $io->text($legacy['latestAvailable'] > $now
                ? "Legacy queue clear time, best case: {$lastEligible} UTC (when the last becomes eligible for delivery, if each is sent first time)"
                : "Legacy queue clear time, best case: now (all eligible for delivery since {$lastEligible} UTC, if each is sent first time)"
            );

            // Worst case: every attempt fails, and each retry waits its longest, until the last one goes to "failed"
            if ($legacy['unestimatedQueues']) {
                $io->text('Legacy queue clear time, worst case: no estimate (no known retry policy for queue(s) ' . join(', ', array_keys($legacy['unestimatedQueues'])) . ')');
            } else {
                $io->text(sprintf(
                    'Legacy queue clear time, worst case (approx.): %s UTC (if every attempt fails and each retry waits its longest)',
                    (new \DateTimeImmutable('@' . (int) ceil($legacy['clearByMs'] / 1000)))->format('Y-m-d H:i:s'),
                ));
            }
            $io->text('Both assume workers keep running; a backlog or slow processing makes them later.');
        }
        if ($blockingIds) {
            $io->text('Unrecognised / unreadable outstanding message IDs: ' . join(', ', $blockingIds));
        }

        $io->section('Failed messages (information only: never reprocessed, never affect the result)');
        $this->report($io, 'Failed', $tallies['failed']);
        if ($failedCreated[0] !== null) {
            $io->text("Created {$failedCreated[0]} to {$failedCreated[1]} UTC");
        }

        $counts = $tallies['outstanding']['counts'];
        if ($counts[self::LEGACY] + $counts[self::UNRECOGNISED] + $counts[self::UNREADABLE] === 0) {
            $io->success('Safe to deploy: no outstanding survey-originated messages');
            return Command::SUCCESS;
        }

        $io->error(sprintf(
            'NOT safe to deploy: %d legacy, %d unrecognised and %d unreadable outstanding message(s)',
            $counts[self::LEGACY], $counts[self::UNRECOGNISED], $counts[self::UNREADABLE],
        ));
        return Command::FAILURE;
    }

    /** @return iterable<array<string, mixed>> Bounded pages (bodies can total hundreds of MiB; PDO buffers result sets) */
    private function rows(): iterable
    {
        $lastId = 0;
        do {
            $page = $this->connection->fetchAllAssociative(
                'SELECT id, queue_name, body, created_at, available_at, delivered_at FROM messenger_messages WHERE id > ? ORDER BY id LIMIT ?',
                [$lastId, $this->pageSize],
                [ParameterType::INTEGER, ParameterType::INTEGER],
            );

            foreach ($page as $row) {
                yield $row;
                $lastId = (int) $row['id'];
            }
        } while (count($page) === $this->pageSize);
    }

    /**
     * The longest time the remaining retries can add, as MultiplierRetryStrategy::getWaitingTime() computes each
     * wait (delay × multiplier^retries, plus up to jitter × that, capped at max_delay)
     */
    private function longestRemainingWaitMs(array $policy, int $retries): int
    {
        $total = 0;
        for ($k = $retries; $k < $policy['max_retries']; $k++) {
            $wait = $policy['delay'] * $policy['multiplier'] ** $k;
            $wait += (int) ($wait * $policy['jitter']);
            if ($policy['max_delay'] !== 0 && $wait > $policy['max_delay']) {
                $wait = $policy['max_delay'];
            }
            $total += (int) ceil($wait);
        }

        return $total;
    }

    /** @return array{string, string, string, string, int} classification, message class, origin, event name, retry count */
    private function classify(string $body): array
    {
        $envelope = $this->decodeEnvelope($body);
        if ($envelope === null) {
            return [self::UNREADABLE, '-', '-', '-', 0];
        }

        $message = (array) $envelope[self::ENVELOPE_MESSAGE];
        $redeliveries = $envelope[self::ENVELOPE_STAMPS][RedeliveryStamp::class] ?? [];
        $lastRedelivery = is_array($redeliveries) ? end($redeliveries) : false;
        $retries = $lastRedelivery instanceof \__PHP_Incomplete_Class ? (int) (((array) $lastRedelivery)[self::RETRY_COUNT] ?? 0) : 0;

        $class = $message[self::CLASS_NAME];
        $origin = $message[self::ORIGIN] ?? null;
        $event = $message[self::EVENT] ?? null;
        $describe = fn(string $classification) => [$classification, $class, is_string($origin) ? $origin : '-', is_string($event) ? $event : '-', $retries];

        if (in_array($class, self::ORIGINLESS_MESSAGES, true)) {
            return $describe(self::CURRENT);
        }

        $isLegacyOrigin = in_array($origin, self::LEGACY_ORIGINS, true);

        if (in_array($class, self::PRE_BUNDLE_MESSAGES, true)) {
            return $describe($isLegacyOrigin ? self::LEGACY : self::UNRECOGNISED);
        }

        if (in_array($class, self::NOTIFY_MESSAGES, true)) {
            return $describe(match (true) {
                $isLegacyOrigin => self::LEGACY,
                in_array($origin, $this->currentOrigins(), true) => self::CURRENT,
                default => self::UNRECOGNISED,
            });
        }

        return $describe(self::UNRECOGNISED);
    }

    /** @return list<string> */
    private function currentOrigins(): array
    {
        return [
            DomesticSurvey::getNotificationClass(),
            InternationalSurvey::getNotificationClass(),
            PreEnquiry::getNotificationClass(),
            RoRoUser::class,
        ];
    }

    /**
     * Decodes as PhpSerializer::decode() does, but without instantiating any class: every object comes back as
     * __PHP_Incomplete_Class, whose properties are read by their mangled names. Like PhpSerializer, any parse
     * warning (e.g. trailing data, which PHP otherwise accepts) makes the body unreadable.
     *
     * @return array<string, mixed>|null the envelope, as an array of its properties (its message is an incomplete object)
     */
    private function decodeEnvelope(string $body): ?array
    {
        if (!str_ends_with($body, '}')) {
            $body = base64_decode($body);
        }

        set_error_handler(static function (int $type, string $message): bool {
            if (in_array($type, [\E_DEPRECATED, \E_USER_DEPRECATED], true)) {
                return false;
            }
            throw new \ErrorException($message, 0, $type);
        });

        try {
            $envelope = unserialize(stripslashes($body), ['allowed_classes' => false]);
        } catch (\Throwable) {
            return null;
        } finally {
            restore_error_handler();
        }

        if (!$envelope instanceof \__PHP_Incomplete_Class) {
            return null;
        }

        $envelope = (array) $envelope;
        $message = $envelope[self::ENVELOPE_MESSAGE] ?? null;
        if (($envelope[self::CLASS_NAME] ?? null) !== Envelope::class || !$message instanceof \__PHP_Incomplete_Class) {
            return null;
        }

        return $envelope;
    }

    /** @return array{counts: array<string, int>, rows: array<string, int>} */
    private function emptyTally(): array
    {
        return ['counts' => array_fill_keys(self::CLASSIFICATIONS, 0), 'rows' => []];
    }

    /** @param array{counts: array<string, int>, rows: array<string, int>} $tally */
    private function report(SymfonyStyle $io, string $label, array $tally): void
    {
        if ($tally['rows']) {
            ksort($tally['rows']);
            $io->table(
                ['Queue', 'Classification', 'Message', 'Origin', 'Event', 'Count'],
                array_map(fn(string $key, int $count) => [...explode("\0", $key), $count], array_keys($tally['rows']), $tally['rows']),
            );
        }

        $io->text(sprintf(
            '%s: %d legacy, %d current, %d unrecognised, %d unreadable',
            $label, ...array_values($tally['counts']),
        ));
    }
}
