<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\Domestic\Survey;
use App\Repository\PasscodeUserRepository;
use DateTime;
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
     * @var PasscodeUserRepository
     */
    private $passcodeUserRepository;

    public function __construct(EntityManagerInterface $entityManager, PasscodeUserRepository $passcodeUserRepository)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passcodeUserRepository = $passcodeUserRepository;
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
            ->setInvitationEmail('test@example.com')
            ->setInvitationAddress((new Address())
                ->setLine1('123 Test Street')
                ->setLine2('Test Town or City')
                ->setPostcode('B10 9TJ')
            )
            ->setRegistrationMark($reg)
            ->setSurveyPeriodStart(new DateTime('now +7 days'))
            ->setSurveyPeriodEnd(new DateTime('now +14 days'))
            ->setIsNorthernIreland(false)
            ->setReminderState(Survey::REMINDER_STATE_NOT_WANTED)
            ->setPasscodeUser($user = $this->passcodeUserRepository->createNewPasscodeUser())
        ;

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Domestic survey created');
        $io->writeln("Vehicle reg         : {$reg}");
        $io->writeln("Pass code 1         : {$user->getUsername()}");
        $io->writeln("Pass code 2         : {$user->getPlainPassword()}");
        $io->writeln("Survey period start : {$survey->getSurveyPeriodStart()->format('Y-m-d')}");
        $io->writeln("Survey period end   : {$survey->getSurveyPeriodEnd()->format('Y-m-d')}");
        $io->writeln("");

        return 0;
    }

}
