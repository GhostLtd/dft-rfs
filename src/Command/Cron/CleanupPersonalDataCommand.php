<?php

namespace App\Command\Cron;

use App\Utility\Cleanup\PersonalDataCleanupUtility;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:cron:cleanup:personal-data')]
class CleanupPersonalDataCommand extends Command
{
    public function __construct(protected PersonalDataCleanupUtility $cleanupUtility)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Deletes personal data for surveys that have reached the cleanup time limit')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $total = $this->cleanupUtility->cleanupPersonalData();
        }
        catch(\Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $io->success(sprintf("Success - cleared personal data from %d survey(s)", $total));
        return 0;
    }
}
