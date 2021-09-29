<?php

namespace App\Command;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronCleanupPasscodeUsersCommand extends Command
{
    protected static $defaultName = 'app:cron:cleanup:passcode-users';

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
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

        $rejectedBefore = new \DateTime('6 months ago');

        $count = 0;

        $domesticRepo = $this->entityManager->getRepository(DomesticSurvey::class);
        foreach($domesticRepo->getRejectedSurveysThatHaveUsersBefore($rejectedBefore) as $survey) {
            $this->entityManager->remove($survey->getPasscodeUser());
            $survey->setPasscodeUser(null);
            $count++;
        }

        $internationalRepo = $this->entityManager->getRepository(InternationalSurvey::class);
        foreach($internationalRepo->getRejectedSurveysThatHaveUsersBefore($rejectedBefore) as $survey) {
            $this->entityManager->remove($survey->getPasscodeUser());
            $survey->setPasscodeUser(null);
            $count++;
        }

        $this->entityManager->flush();
        $io->success(sprintf("Success - cleared %d passcode user(s)", $count));

        return 0;
    }
}
