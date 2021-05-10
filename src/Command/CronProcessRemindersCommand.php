<?php

namespace App\Command;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\SurveyInterface;
use App\Utility\AlphagovNotify\TemplateReference;
use App\Utility\RemindersHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Doctrine\ORM\QueryBuilder;

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
            ->addOption('dry-run', null, InputOption::VALUE_NONE, "Run the process without sending reminders or updating the entities/status")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

//        $remindersSurveys = $this->remindersHelper->getSurveysForReminding();
        $this->remindersHelper->sendReminders();

        $io->success("Success");

        return 0;
    }
}
