<?php

namespace App\Command\Dev;

use App\Utility\Dev\DastSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Seeds the DAST stack's empty database (bin/dast, docs/Dast.md). Prints KEY=value lines on stdout for bin/dast to
 * hand to ZAP; everything else goes to stderr.
 */
#[AsCommand('rfs:dev:dast-seed', 'Seed an empty database for the DAST scan (bin/dast)')]
class DastSeedCommand extends Command
{
    public function __construct(
        private readonly DastSeeder $seeder,
        private readonly ?string $appEnvironment,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->appEnvironment !== 'staging') {
            throw new \RuntimeException('must be running in "staging" app environment');
        }

        $values = $this->seeder->seed();

        foreach ($values as $key => $value) {
            $output->writeln("{$key}={$value}", OutputInterface::OUTPUT_RAW);
        }

        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $errorOutput->writeln(sprintf('Seeded %d DAST users', count(DastSeeder::USERNAMES)));

        return Command::SUCCESS;
    }
}
