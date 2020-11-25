<?php

namespace App\Command;

use App\Entity\International\Survey;
use App\Entity\PasscodeUser;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateInternationalSurveyCommand extends Command
{
    protected static $defaultName = 'rfs:international:create-survey';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PasscodeGenerator
     */
    private $passcodeGenerator;

    public function __construct(EntityManagerInterface $entityManager, PasscodeGenerator $passcodeGenerator)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passcodeGenerator = $passcodeGenerator;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create a new international survey, and passcodes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $survey = new Survey();
        $survey
            // ToDo: update reference number
            ->setReferenceNumber(1)
            ->setStartDate(new \DateTime('now +7 days'))
        ;
        $user = new PasscodeUser();
        $user
            ->setUsername($username = $this->passcodeGenerator->generatePasscode())
            ->setPlainPassword($password = $this->passcodeGenerator->generatePasscode())
            ->setInternationalSurvey($survey);
        ;

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('International survey created');
        $io->writeln("Pass code 1 : {$username}");
        $io->writeln("Pass code 2 : {$password}");
        $io->writeln("Survey due  : {$survey->getStartDate()->format('Y-m-d')}");
        $io->writeln("");

        return 0;
    }

}
