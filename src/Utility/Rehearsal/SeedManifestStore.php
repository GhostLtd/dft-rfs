<?php

namespace App\Utility\Rehearsal;

use App\Entity\AuditLog\AuditLog;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Stores the rehearsal manifest in one audit_log row. New rows require the caller to flush.
 */
class SeedManifestStore
{
    public const string CATEGORY = 'reminder-seed';
    public const string ENTITY_CLASS = 'seed';
    public const string ENTITY_ID = 'reminder-scenarios';

    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /**
     * Returns the stored seed manifest, or null when no manifest row exists.
     */
    public function find(): ?SeedManifest
    {
        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT data FROM audit_log WHERE category = ? AND entity_class = ? AND entity_id = ?',
            [self::CATEGORY, self::ENTITY_CLASS, self::ENTITY_ID],
        );
        if ($row === false) {
            return null;
        }

        // The exact seeding instant is in the JSON; audit_log.timestamp is informational only.
        return SeedManifest::fromArray(json_decode($row['data'], true, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * Persists a new manifest audit row; the caller flushes it.
     */
    public function write(SeedManifest $manifest): void
    {
        $this->entityManager->persist((new AuditLog())
            ->setCategory(self::CATEGORY)
            ->setEntityClass(self::ENTITY_CLASS)
            ->setEntityId(self::ENTITY_ID)
            ->setUsername('seed')
            ->setTimestamp(\DateTime::createFromImmutable($manifest->seededAt))
            ->setData($manifest->toArray()));
    }
}
