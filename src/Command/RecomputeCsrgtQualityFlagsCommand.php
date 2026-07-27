<?php

namespace App\Command;

use App\Entity\Domestic\SurveyResponse;
use App\Utility\QualityCheck\QualityCheckEngine;
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
    use MySqlGoneAwayRetryTrait;

    private const int BATCH_SIZE = 100;

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
}
