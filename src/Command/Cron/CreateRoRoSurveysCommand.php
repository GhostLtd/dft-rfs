<?php

namespace App\Command\Cron;

use App\Utility\RoRo\SurveyCreationHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:cron:roro:create-surveys')]
class CreateRoRoSurveysCommand extends Command
{
    public function __construct(protected SurveyCreationHelper $surveyCreationHelper)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Creates the necessary surveys for all active operators/routes in the current period')
        ;
    }

    #[\Override]
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
