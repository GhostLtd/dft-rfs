<?php

namespace App\Command;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Makes long-running commands resilient to intermittent "MySQL server has gone away" (error 2006)
 * failures: wrap each self-contained (and therefore safely retryable) database unit of work in
 * {@see withRetry()}, which reconnects and retries with exponential backoff.
 *
 * The using class must have a mutable `$entityManager` (EntityManagerInterface) property and a
 * `$registry` (ManagerRegistry) property — {@see reconnect()} replaces the EntityManager if a
 * failed flush has closed it.
 */
trait MySqlGoneAwayRetryTrait
{
    private const int MAX_DB_ATTEMPTS = 10;
    private const int BASE_DELAY_MS = 250;
    private const int MAX_DELAY_MS = 30_000;

    /**
     * Run a database operation, retrying with exponential backoff if the connection is lost
     * ("MySQL server has gone away" and similar). Reconnects between attempts; rethrows once
     * {@see MAX_DB_ATTEMPTS} is exhausted.
     *
     * @template T
     * @param callable(): T $op
     * @return T
     */
    private function withRetry(SymfonyStyle $io, string $what, callable $op): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $op();
            } catch (ConnectionException $e) {
                if ($attempt >= self::MAX_DB_ATTEMPTS) {
                    $io->error(sprintf(
                        'Database connection lost while %s and did not recover after %d attempts.',
                        $what,
                        self::MAX_DB_ATTEMPTS,
                    ));
                    throw $e;
                }

                $delayMs = $this->backoffDelayMs($attempt);
                $io->warning(sprintf(
                    'Database connection lost while %s (attempt %d/%d): %s — reconnecting in %.1fs…',
                    $what,
                    $attempt,
                    self::MAX_DB_ATTEMPTS,
                    $e->getMessage(),
                    $delayMs / 1000,
                ));

                usleep($delayMs * 1000);
                $this->reconnect();
            }
        }
    }

    private function backoffDelayMs(int $attempt): int
    {
        return (int) min(self::BASE_DELAY_MS * (2 ** ($attempt - 1)), self::MAX_DELAY_MS);
    }

    private function reconnect(): void
    {
        // A failure during flush() leaves the EntityManager closed; it must be replaced before use.
        if (!$this->entityManager->isOpen()) {
            $manager = $this->registry->resetManager();
            assert($manager instanceof EntityManagerInterface);
            $this->entityManager = $manager;
        }

        // Drop the stale connection so the next query establishes a fresh one.
        $connection = $this->entityManager->getConnection();
        if ($connection->isConnected()) {
            $connection->close();
        }
    }
}
