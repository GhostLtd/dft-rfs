<?php

namespace App\Utility\Rehearsal;

/** The seed's metadata, stored as one audit_log row (see SeedManifestStore). */
final class SeedManifest
{
    private const string STALE_AFTER = 'PT24H';

    /**
     * @param array{from: string, disabled: list<string>, smsCap: int} $settings
     * @param array<string, array{class: class-string, id: string}> $scenarios
     * @param string $roroUserId the seeded RoRo user, which holds the RoRo scenarios' Notify responses
     * @param list<string> $historyRowIds notification rows the seed created as history (everything else was the run)
     */
    public function __construct(
        public readonly \DateTimeImmutable $seededAt,
        public readonly array $settings,
        public readonly array $scenarios,
        public readonly string $roroUserId,
        public readonly array $historyRowIds,
    ) {}

    /**
     * Returns IDs of seeded surveys belonging to the supplied class.
     *
     * @return list<string>
     */
    public function surveyIds(string $class): array
    {
        return array_values(array_map(fn(array $s) => $s['id'], array_filter($this->scenarios, fn(array $s) => $s['class'] === $class)));
    }

    /**
     * Returns whether the seed is more than 24 hours old.
     */
    public function isStale(\DateTimeImmutable $now): bool
    {
        return $now > $this->seededAt->add(new \DateInterval(self::STALE_AFTER));
    }

    /**
     * Returns manifest data for JSON storage, with seededAt formatted as ISO 8601 including its time-zone offset.
     */
    public function toArray(): array
    {
        return [
            'seededAt' => $this->seededAt->format(\DATE_ATOM),
            'settings' => $this->settings,
            'scenarios' => $this->scenarios,
            'roroUserId' => $this->roroUserId,
            'historyRowIds' => $this->historyRowIds,
        ];
    }

    /**
     * Recreates a manifest from its stored array.
     */
    public static function fromArray(array $data): self
    {
        return new self(new \DateTimeImmutable($data['seededAt']), $data['settings'], $data['scenarios'], $data['roroUserId'], $data['historyRowIds']);
    }
}
