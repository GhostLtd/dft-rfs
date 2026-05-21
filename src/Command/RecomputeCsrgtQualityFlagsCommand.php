<?php

namespace App\Command;

use App\Entity\Domestic\SurveyResponse;
use App\Utility\QualityCheck\QualityCheckEngine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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

    public function __construct(
        private readonly QualityCheckEngine $engine,
        private readonly EntityManagerInterface $entityManager,
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

        $total = $this->countResponses($state, $limit);
        if ($total === 0) {
            $io->warning('No responses match the filter.');
            return Command::SUCCESS;
        }

        $io->progressStart($total);

        $processed = 0;
        $flagged = 0;

        foreach ($this->buildQueryBuilder($state, $limit)->getQuery()->toIterable() as $response) {
            $set = $this->engine->computeAndPersist($response);
            if (!$set->isEmpty()) {
                $flagged++;
            }
            $processed++;
            $io->progressAdvance();

            if ($processed % self::BATCH_SIZE === 0) {
                $this->flushBatch($dryRun);
            }
        }

        $this->flushBatch($dryRun);
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

    private function countResponses(?string $state, ?int $limit): int
    {
        $qb = $this->buildQueryBuilder($state, limit: null)
            ->select('COUNT(r.id)');

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        return $limit !== null ? min($count, $limit) : $count;
    }

    private function buildQueryBuilder(?string $state, ?int $limit): QueryBuilder
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('r')
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

    private function flushBatch(bool $dryRun): void
    {
        if (!$dryRun) {
            $this->entityManager->flush();
        }
        $this->entityManager->clear();
    }
}
