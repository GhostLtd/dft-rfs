<?php

namespace App\Command\Cron;

use App\Utility\Reminder\AutomatedRemindersHelper;
use App\Utility\Reminder\AutomatedRoroRemindersHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:cron:reminders:process')]
class ProcessRemindersCommand extends Command
{
    public function __construct(
        protected AutomatedRemindersHelper $automatedRemindersHelper,
        protected AutomatedRoroRemindersHelper $automatedRoroRemindersHelper,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Send reminders for surveys that need them.')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->automatedRemindersHelper->sendReminders();
        $this->automatedRoroRemindersHelper->sendReminders();

        $io->success("Success");

        return 0;
    }
}
