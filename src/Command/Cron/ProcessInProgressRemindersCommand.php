<?php

namespace App\Command\Cron;

use App\Utility\Notification\AutomatedNotificationsRunner;
use App\Utility\Reminder\DomesticInProgressReminders;
use App\Utility\Reminder\DryRun\ReminderDryRun;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:cron:reminders:process-in-progress')]
class ProcessInProgressRemindersCommand extends Command
{
    use DryRunOptionsTrait;

    public function __construct(
        protected AutomatedNotificationsRunner $runner,
        protected DomesticInProgressReminders $reminders,
        protected ?ReminderDryRun $dryRun = null,
        protected ?ClockInterface $clock = null,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Send CSRGT in-progress reminders (email and SMS) for surveys that need them.');
        $this->configureDryRunOptions(true);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($misuse = $this->dryRunMisuse($input)) {
            $io->error($misuse);
            return Command::INVALID;
        }
        if ($error = $this->dryRunOptionsError($input)) {
            $io->error($error);
            return Command::INVALID;
        }
        if ($input->getOption('dry-run')) {
            return $this->dryRun->run([$this->reminders], $this->dryRunOptions($input, $this->clock), $io);
        }

        $this->runner->runOne($this->reminders);
        $io->success("Success");
        return Command::SUCCESS;
    }
}
