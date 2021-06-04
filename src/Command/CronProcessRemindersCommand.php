<?php

namespace App\Command;

use App\Utility\RemindersHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronProcessRemindersCommand extends Command
{
    protected static $defaultName = 'app:cron:reminders:process';

    /**
     * @var RemindersHelper
     */
    private RemindersHelper $remindersHelper;

    public function __construct(RemindersHelper $remindersHelper)
    {
        parent::__construct();
        $this->remindersHelper = $remindersHelper;
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

        $this->remindersHelper->sendReminders();

        $io->success("Success");

        return 0;
    }
}
