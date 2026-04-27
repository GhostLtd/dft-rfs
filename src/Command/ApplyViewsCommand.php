<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    'app:views:apply',
    'Apply SQL view and function definitions from sql/ to the database'
)]
class ApplyViewsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print what would be applied without executing any SQL')
            ->addOption('only', null, InputOption::VALUE_REQUIRED, 'Apply only the named object (and its dependencies unless --no-deps)')
            ->addOption('no-deps', null, InputOption::VALUE_NONE, 'With --only, skip the named object\'s dependencies');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $platform = $this->entityManager->getConnection()->getDatabasePlatform();
        if (!$platform instanceof AbstractMySQLPlatform) {
            $io->note(sprintf('Skipping: views/functions are MySQL-specific and the current platform is %s', $platform::class));
            return Command::SUCCESS;
        }

        $objects = $this->loadObjects();
        if ($objects === []) {
            $io->warning(sprintf('No SQL files found under %s/sql/', $this->projectDir));
            return Command::SUCCESS;
        }

        try {
            $ordered = $this->topologicalSort($objects);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $only = $input->getOption('only');
        if ($only !== null) {
            if (!isset($objects[$only])) {
                $io->error(sprintf('No object named "%s" - expected a file at sql/views/%s.sql or sql/functions/%s.sql', $only, $only, $only));
                return Command::FAILURE;
            }
            $ordered = $this->restrictToOnly($ordered, $only, withDeps: !$input->getOption('no-deps'));
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $verbose = $output->isVerbose();
        $connection = $this->entityManager->getConnection();

        foreach ($ordered as $obj) {
            $io->writeln(sprintf('%sapplying %s %s', $dryRun ? '[dry-run] ' : '', $obj->type->value, $obj->name));

            if ($verbose || $dryRun) {
                if ($obj->type === SqlObjectType::Function) {
                    $io->writeln('  DROP FUNCTION IF EXISTS ' . $obj->name);
                }
                foreach (explode("\n", rtrim($obj->body)) as $line) {
                    $io->writeln('  ' . $line);
                }
            }

            if ($dryRun) {
                continue;
            }

            if ($obj->type === SqlObjectType::Function) {
                $connection->executeStatement('DROP FUNCTION IF EXISTS ' . $obj->name);
            }
            $connection->executeStatement($obj->body);
        }

        $io->success(sprintf('%s %d object(s)', $dryRun ? 'Dry run OK:' : 'Applied', count($ordered)));
        return Command::SUCCESS;
    }

    /**
     * @return array<string, SqlObject>
     */
    private function loadObjects(): array
    {
        $objects = [];
        foreach ([
            [SqlObjectType::View, 'views'],
            [SqlObjectType::Function, 'functions'],
        ] as [$type, $subdir]) {
            $dir = $this->projectDir . '/sql/' . $subdir;
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*.sql') ?: [] as $path) {
                $obj = SqlObject::fromFile($path, $type);
                if (isset($objects[$obj->name])) {
                    throw new \RuntimeException(sprintf('Duplicate object name "%s" (found under both sql/views and sql/functions)', $obj->name));
                }
                $objects[$obj->name] = $obj;
            }
        }
        return $objects;
    }

    /**
     * @param array<string, SqlObject> $objects
     * @return list<SqlObject>
     */
    private function topologicalSort(array $objects): array
    {
        $sortedNames = [];
        $state = []; // 0 = unvisited, 1 = visiting, 2 = done
        $names = array_keys($objects);
        sort($names);

        $visit = function (string $name, array $stack) use (&$visit, &$state, &$sortedNames, $objects): void {
            if (($state[$name] ?? 0) === 2) {
                return;
            }
            if (($state[$name] ?? 0) === 1) {
                $cycle = array_slice($stack, (int) array_search($name, $stack, true));
                $cycle[] = $name;
                throw new \RuntimeException('Dependency cycle: ' . implode(' -> ', $cycle));
            }
            if (!isset($objects[$name])) {
                throw new \RuntimeException(sprintf('Unknown dependency "%s" — no file at sql/views/%s.sql or sql/functions/%s.sql', $name, $name, $name));
            }
            $state[$name] = 1;
            $stack[] = $name;
            foreach ($objects[$name]->depends as $dep) {
                $visit($dep, $stack);
            }
            $state[$name] = 2;
            $sortedNames[] = $name;
        };

        foreach ($names as $name) {
            $visit($name, []);
        }

        return array_map(static fn (string $n): SqlObject => $objects[$n], $sortedNames);
    }

    /**
     * Trim $ordered down to just $name (and its transitive deps if $withDeps).
     *
     * @param list<SqlObject> $ordered
     * @return list<SqlObject>
     */
    private function restrictToOnly(array $ordered, string $name, bool $withDeps): array
    {
        // Index the ordered list by name so the dependency walk can hop from one object to the next.
        $byName = [];
        foreach ($ordered as $obj) {
            $byName[$obj->name] = $obj;
        }

        if (!$withDeps) {
            return [$byName[$name]];
        }

        // Walk the dependency graph from $name, collecting every reachable object name into a set.
        $needed = [];
        $queue = [$name];
        while ($queue) {
            $current = array_shift($queue);
            if (isset($needed[$current])) {
                continue;
            }
            $needed[$current] = true;
            foreach ($byName[$current]->depends as $dep) {
                if (isset($byName[$dep]) && !isset($needed[$dep])) {
                    $queue[] = $dep;
                }
            }
        }

        // Keep entries from $ordered whose name is in the reachable set; preserves the topological order.
        return array_values(array_filter($ordered, static fn (SqlObject $o) => isset($needed[$o->name])));
    }
}
