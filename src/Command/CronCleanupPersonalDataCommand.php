<?php

namespace App\Command;

use App\Utility\Cleanup\PersonalDataCleanupUtility;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:cron:cleanup:personal-data')]
class CronCleanupPersonalDataCommand extends Command
{
    public function __construct(protected PersonalDataCleanupUtility $cleanupUtility)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Deletes personal data for exported,reissued and rejected surveys after six months')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $count = $this->cleanupUtility->cleanupPersonalDataBefore(new \DateTime('6 months ago'));
        }
        catch(\Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $io->success(sprintf("Success - cleared personal data from %d survey(s)", $count));
        return 0;
    }
}
