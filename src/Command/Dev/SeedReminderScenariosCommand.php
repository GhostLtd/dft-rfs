<?php

namespace App\Command\Dev;

use App\Utility\DatabaseDescription;
use App\Utility\Rehearsal\BaselineGuard;
use App\Utility\Rehearsal\ScenarioCatalogue;
use App\Utility\Rehearsal\ScenarioOutcome;
use App\Utility\Rehearsal\ScenarioSeeder;
use App\Utility\Rehearsal\SeedManifestStore;
use App\Utility\Rehearsal\SeedRefusedException;
use App\Utility\Rehearsal\SeedReporter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:dev:seed-reminder-scenarios', 'Seed or report on the reminder rehearsal scenarios (see docs/RemindersRehearsal.md)')]
class SeedReminderScenariosCommand extends Command
{
    private const string RECREATE = 'Recreate the scratch database (docs/RemindersRehearsal.md, step A2), then seed again.';

    public function __construct(
        private readonly BaselineGuard $baselineGuard,
        private readonly SeedManifestStore $manifestStore,
        private readonly DatabaseDescription $databaseDescription,
        private readonly ScenarioSeeder $seeder,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
        private readonly SeedReporter $reporter,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('report', null, InputOption::VALUE_NONE, "Don't seed; report each scenario's outcome")
            ->addOption('check-baseline', null, InputOption::VALUE_NONE, "Don't seed; read-only: check the database is ready for seeding (no operational data)")
            ->addOption('force', null, InputOption::VALUE_NONE, 'Skip the typed database-name confirmation');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->writeln('Database: '.$this->databaseDescription->describe());

        if ($input->getOption('check-baseline')) {
            return $this->checkBaseline($io);
        }

        if ($input->getOption('report')) {
            return $this->report($io);
        }

        return $this->seed($io, (bool) $input->getOption('force'));
    }

    private function seed(SymfonyStyle $io, bool $force): int
    {
        if (!$this->confirmDatabaseName($io, $force)) {
            return Command::FAILURE;
        }

        $now = \DateTimeImmutable::createFromInterface($this->clock->now());
        $settings = $this->seeder->getSettings();
        if ($refusal = $this->seeder->getRefusalReason($now, $settings)) {
            $io->error($refusal);
            return Command::FAILURE;
        }
        foreach (array_intersect($settings['disabled'], ['csrgt', 'irhs', 'pre-enquiry', 'roro', 'csrgt-in-progress']) as $family) {
            $io->warning("APP_DISABLE_REMINDERS includes {$family}: its scenarios will be seeded but not sent.");
        }

        if ($existing = $this->manifestStore->find()) {
            $io->error(sprintf('This database already holds a seed (from %s). %s', $existing->seededAt->format('Y-m-d H:i'), self::RECREATE));
            return Command::FAILURE;
        }
        if ($problems = $this->baselineGuard->problems()) {
            $io->error(['This database holds operational data, so it is not a freshly prepared rehearsal database:', ...$problems, self::RECREATE]);
            return Command::FAILURE;
        }

        try {
            $manifest = $this->seeder->seed($now, $settings);
        } catch (SeedRefusedException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $rows = [];
        foreach (ScenarioCatalogue::all() as $scenario) {
            $entry = $manifest->scenarios[$scenario->name];
            $rows[] = [$scenario->name, $entry['id'], $scenario->registrationMark ?? $scenario->reference ?? 'RoRo', $scenario->expectText];
        }
        $io->table(['scenario', 'survey id', 'mark / reference', 'expect'], $rows);
        $io->success(sprintf('Seeded %d scenarios at %s (APP_IN_PROGRESS_REMINDERS_FROM=%s).', count($rows), $manifest->seededAt->format('Y-m-d H:i'), $settings['from']));
        return Command::SUCCESS;
    }

    /**
     * Returns true when --force is set or the entered database name matches the target.
     * Prints an error and returns false otherwise.
     */
    private function confirmDatabaseName(SymfonyStyle $io, bool $force): bool
    {
        if ($force) {
            return true;
        }
        $params = $this->entityManager->getConnection()->getParams();
        $name = (string) ($params['dbname'] ?? basename((string) ($params['path'] ?? '')));
        $answer = $io->ask("This writes to {$this->databaseDescription->describe()}. Type the database name ({$name}) to confirm");
        if ($answer !== $name) {
            $io->error('Not confirmed; nothing changed.');
            return false;
        }
        return true;
    }

    private function report(SymfonyStyle $io): int
    {
        $manifest = $this->manifestStore->find();
        if ($manifest === null) {
            $io->error('No seed found. Seed first: app:dev:seed-reminder-scenarios');
            return Command::FAILURE;
        }
        $now = \DateTimeImmutable::createFromInterface($this->clock->now());
        if ($refusal = $this->reporter->getRefusalReason($manifest, $now)) {
            $io->error($refusal);
            return Command::FAILURE;
        }

        $current = $this->seeder->getSettings();
        $io->writeln(sprintf('Effective settings: APP_IN_PROGRESS_REMINDERS_FROM=%s  APP_DISABLE_REMINDERS=%s  APP_SMS_ANNUAL_CAP=%d   (seeded %s)',
            $current['from'] ?: '(unset)', $current['disabled'] === [] ? '(none)' : implode(',', $current['disabled']), $current['smsCap'], $manifest->seededAt->format('Y-m-d H:i')));
        if ($warning = $this->reporter->settingsWarning($manifest->settings, $current)) {
            $io->warning($warning);
        }

        $outcomes = $this->reporter->outcomes($manifest, $now);
        $io->table(['', 'scenario', 'survey id', 'expected', 'detail'], array_map(fn(ScenarioOutcome $o) => [$o->verdict, $o->scenario->name, $o->surveyId, $o->scenario->expectText, $o->detail], $outcomes));

        $sent = $this->entityManager->getConnection()->fetchOne('SELECT COALESCE(SUM(sent), 0) FROM sms_usage');
        $io->writeln("SMS allowance used (sms_usage): {$sent}");

        $failed = count(array_filter($outcomes, fn(ScenarioOutcome $o) => $o->verdict === ScenarioOutcome::FAIL));
        if ($failed > 0) {
            $io->error("{$failed} scenario(s) failed.");
            return Command::FAILURE;
        }
        $io->success('Every scenario is as expected'.(array_filter($outcomes, fn($o) => $o->verdict === ScenarioOutcome::WAIT) ? ' (invites still pending: check again after the 4-hour delay).' : '.'));
        return Command::SUCCESS;
    }

    private function checkBaseline(SymfonyStyle $io): int
    {
        $problems = $this->baselineGuard->problems();
        if ($problems === []) {
            $io->success('At baseline: ready for seeding.');
            return Command::SUCCESS;
        }
        $io->error(['Not ready for seeding:', ...$problems, self::RECREATE]);
        return Command::FAILURE;
    }
}
