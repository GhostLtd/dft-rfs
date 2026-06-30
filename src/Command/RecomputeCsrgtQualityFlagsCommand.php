<?php

namespace App\Command;

use App\Entity\Domestic\SurveyResponse;
use App\Utility\QualityCheck\QualityCheckEngine;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rfs:csrgt:quality-flags:recompute',
    description: 'Recompute the CSRGT QualityCheck aggregate (count) on every domestic SurveyResponse',
)]
class RecomputeCsrgtQualityFlagsCommand extends Command
{
    private const int BATCH_SIZE = 100;

    /**
     * This command can run for a long time against the database, and we have seen intermittent
     * "MySQL server has gone away" (error 2006) failures. To survive those, each database unit of
     * work below is wrapped in {@see withRetry()}, which reconnects and retries with exponential
     * backoff. Recompute is idempotent, so re-running a batch after a reconnect is safe.
     */
    private const int MAX_DB_ATTEMPTS = 10;
    private const int BASE_DELAY_MS = 250;
    private const int MAX_DELAY_MS = 30_000;

    public function __construct(
        private readonly QualityCheckEngine $engine,
        private readonly ManagerRegistry $registry,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Cap the number of responses processed (useful for testing).')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Compute aggregates but do not persist them.')
            ->addOption('state', null, InputOption::VALUE_REQUIRED, 'Filter by survey state (e.g. closed). If omitted, all states are processed.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = $input->getOption('limit') !== null ? (int) $input->getOption('limit') : null;
        $dryRun = (bool) $input->getOption('dry-run');
        $state = $input->getOption('state');

        $io->section('CSRGT QualityCheck backfill' . ($dryRun ? ' (dry run)' : ''));

        $ids = $this->withRetry($io, 'loading response IDs', fn(): array => $this->fetchResponseIds($state, $limit));

        $total = count($ids);
        if ($total === 0) {
            $io->warning('No responses match the filter.');
            return Command::SUCCESS;
        }

        $io->progressStart($total);

        $processed = 0;
        $flagged = 0;

        foreach (array_chunk($ids, self::BATCH_SIZE) as $batch) {
            $flagged += $this->withRetry(
                $io,
                sprintf('processing %d response(s)', count($batch)),
                fn(): int => $this->processBatch($batch, $dryRun),
            );
            $processed += count($batch);
            $io->progressAdvance(count($batch));
        }

        $io->progressFinish();

        $io->success(sprintf(
            'Processed %d response%s%s — %d with flags, %d clean.',
            $processed,
            $processed === 1 ? '' : 's',
            $dryRun ? ' (no changes persisted)' : '',
            $flagged,
            $processed - $flagged,
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array<int, mixed> the SurveyResponse ids matching the filter, ordered stably for batching
     */
    private function fetchResponseIds(?string $state, ?int $limit): array
    {
        $qb = $this->buildQueryBuilder($state, $limit)
            ->select('r.id')
            ->orderBy('r.id', 'ASC');

        return array_column($qb->getQuery()->getScalarResult(), 'id');
    }

    /**
     * Recompute and (unless dry-run) persist the aggregate for a single batch of response ids.
     * Reloads the responses from scratch so the unit is self-contained and safe to retry after a
     * reconnect.
     *
     * @param array<int, mixed> $ids
     * @return int the number of responses in this batch that ended up with at least one flag
     */
    private function processBatch(array $ids, bool $dryRun): int
    {
        $this->entityManager->clear();

        $responses = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(SurveyResponse::class, 'r')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $flagged = 0;
        foreach ($responses as $response) {
            $set = $this->engine->computeAndPersist($response);
            if (!$set->isEmpty()) {
                $flagged++;
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }
        $this->entityManager->clear();

        return $flagged;
    }

    private function buildQueryBuilder(?string $state, ?int $limit): QueryBuilder
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(SurveyResponse::class, 'r')
            ->join('r.survey', 's');

        if ($state !== null) {
            $qb->andWhere('s.state = :state')->setParameter('state', $state);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }

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
