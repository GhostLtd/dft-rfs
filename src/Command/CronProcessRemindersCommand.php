<?php

namespace App\Command;

use App\Utility\Reminder\AutomatedRemindersHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronProcessRemindersCommand extends Command
{
    protected static $defaultName = 'app:cron:reminders:process';

    /**
     * @var AutomatedRemindersHelper
     */
    private AutomatedRemindersHelper $automatedRemindersHelper;

    public function __construct(AutomatedRemindersHelper $automatedRemindersHelper)
    {
        parent::__construct();
        $this->automatedRemindersHelper = $automatedRemindersHelper;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send reminders for surveys that need them.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->automatedRemindersHelper->sendReminders();

        $io->success("Success");

        return 0;
    }
}
