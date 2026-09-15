<?php

namespace App\Command\Cron;

use App\Utility\Notification\AutomatedNotificationsRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:cron:reminders:process')]
class ProcessRemindersCommand extends Command
{
    public function __construct(protected AutomatedNotificationsRunner $runner)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Send automated notifications (reminders) for surveys that need them.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->runner->run();
        (new SymfonyStyle($input, $output))->success("Success");
        return Command::SUCCESS;
    }
}
