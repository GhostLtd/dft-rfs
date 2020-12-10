<?php

namespace App\Command;

use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateDomesticSurveyCommand extends Command
{
    protected static $defaultName = 'rfs:domestic:create-survey';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PasscodeGenerator
     */
    private $passcodeGenerator;

    private $appEnvironment;

    public function __construct(EntityManagerInterface $entityManager, PasscodeGenerator $passcodeGenerator, $appEnvironment)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passcodeGenerator = $passcodeGenerator;
        $this->appEnvironment = $appEnvironment;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create a new domestic survey, and passcodes')
            ->addArgument('reg', InputArgument::OPTIONAL, 'Vehicle registration mark')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $reg = $input->getArgument('reg') ?? "AA19PPP";

        $survey = new Survey();
        $survey
            ->setRegistrationMark($reg)
            ->setStartDate(new \DateTime('now +7 days'))
            ->setIsNorthernIreland(false)
            ->setReminderState(Survey::REMINDER_STATE_NOT_WANTED)
        ;
        $user = new PasscodeUser();
        $user
            ->setUsername($username = $this->passcodeGenerator->generatePasscode())
            ->setPlainPassword($password = ($this->appEnvironment === 'dev' ? 'dev' : $this->passcodeGenerator->generatePasscode()))
            ->setDomesticSurvey($survey);
        ;

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Domestic survey created');
        $io->writeln("Vehicle reg : {$reg}");
        $io->writeln("Pass code 1 : {$username}");
        $io->writeln("Pass code 2 : {$password}");
        $io->writeln("Survey due  : {$survey->getStartDate()->format('Y-m-d')}");
        $io->writeln("");

        return 0;
    }

}
