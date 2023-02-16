<?php

namespace App\Command;

use App\Utility\RoRo\SurveyCreationHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronCreateRoRoSurveysCommand extends Command
{
    protected static $defaultName = 'app:cron:roro:create-surveys';

    protected SurveyCreationHelper $surveyCreationHelper;

    public function __construct(SurveyCreationHelper $surveyCreationHelper)
    {
        parent::__construct();
        $this->surveyCreationHelper = $surveyCreationHelper;
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates the necessary surveys for all active operators/routes in the current period')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->surveyCreationHelper->createSurveysForPreviousMonth();
        }
        catch(\Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $io->success("Success");
        return 0;
    }
}
