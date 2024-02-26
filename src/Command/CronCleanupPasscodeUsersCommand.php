<?php

namespace App\Command;

use App\Utility\Cleanup\PasscodeUserCleanupUtility;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:cron:cleanup:passcode-users')]
class CronCleanupPasscodeUsersCommand extends Command
{
    public function __construct(protected PasscodeUserCleanupUtility $cleanupUtility)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Deletes passcode users for rejected surveys after six months')
        ;
    }

    #[\Override]
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
