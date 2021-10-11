<?php

namespace App\Command;

use App\Utility\Cleanup\PasscodeUserCleanupUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronCleanupPasscodeUsersCommand extends Command
{
    protected static $defaultName = 'app:cron:cleanup:passcode-users';

    protected PasscodeUserCleanupUtility $cleanupUtility;

    public function __construct(PasscodeUserCleanupUtility $cleanupUtility)
    {
        parent::__construct();
        $this->cleanupUtility = $cleanupUtility;
    }

    protected function configure()
    {
        $this
            ->setDescription('Deletes passcode users for rejected surveys after six months')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $count = $this->cleanupUtility->cleanupPasscodeUsersBefore(new \DateTime('6 months ago'));
        }
        catch(\Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $io->success(sprintf("Success - cleared %d passcode user(s)", $count));

        return 0;
    }
}
