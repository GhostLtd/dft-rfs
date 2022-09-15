<?php

namespace App\Command;

use App\Utility\Cleanup\PersonalDataCleanupUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronCleanupPersonalDataCommand extends Command
{
    protected static $defaultName = 'app:cron:cleanup:personal-data';

    protected PersonalDataCleanupUtility $cleanupUtility;

    public function __construct(PersonalDataCleanupUtility $cleanupUtility)
    {
        parent::__construct();
        $this->cleanupUtility = $cleanupUtility;
    }

    protected function configure()
    {
        $this
            ->setDescription('Deletes personal data for exported,reissued and rejected surveys after six months')
        ;
    }

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
