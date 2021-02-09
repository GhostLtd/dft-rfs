<?php

namespace App\Command;

use App\Entity\Domestic\Survey;
use App\Entity\LongAddress;
use App\Entity\PasscodeUser;
use App\Repository\PasscodeUserRepository;
use App\Utility\Domestic\DeleteHelper;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DomesticScreenshotsCommand extends Command
{
    protected static $defaultName = 'rfs:dev:screenshots-domestic';

    protected EntityManagerInterface $entityManager;
    protected DeleteHelper $deleteHelper;

    protected string $frontendHostname;
    protected string $userId = 'screenshot';
    protected string $userPassword = 'screenshot:password';

    public function __construct(EntityManagerInterface $entityManager, DeleteHelper $deleteHelper, string $frontendHostname)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->deleteHelper = $deleteHelper;
        $this->frontendHostname = $frontendHostname;
    }

    protected function configure()
    {
        $this->setDescription('Execute the (external) screenshots utility')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to the screenshots command')
            ->addOption('protocol', null, InputOption::VALUE_OPTIONAL, 'http or https', 'https');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->deleteExistingUser();

        $user = (new PasscodeUser())
            ->setUsername($this->userId)
            ->setPlainPassword($this->userPassword);

        $start = new DateTime();
        $end = (clone $start)->add(new DateInterval('P6D'));

        $survey = (new Survey())
            ->setRegistrationMark('TE01 STT')
            ->setIsNorthernIreland(true)
            ->setSurveyPeriodStart($start)
            ->setSurveyPeriodStart($end)
            ->setInvitationEmail('test@example.com')
            ->setPasscodeUser($user)
            ->setInvitationAddress(new LongAddress());

        $this->entityManager->persist($survey);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $protocol = $input->getOption('protocol');
        $process = new Process([$input->getArgument('path'), "domestic", "{$protocol}:/{$this->frontendHostname}/", "--username={$this->userId}", "--password={$this->userPassword}"]);

        try {
            $process->mustRun();

            echo $process->getOutput();
        } catch (ProcessFailedException $exception) {
            echo $exception->getMessage();
        }

        $this->deleteExistingUser();

        return 0;
    }

    protected function deleteExistingUser()
    {
        /** @var PasscodeUserRepository $repo */
        $userRepo = $this->entityManager->getRepository(PasscodeUser::class);

        $this->entityManager->clear();
        $user = $userRepo->findOneBy(['username' => $this->userId]);

        if ($user && $user instanceof PasscodeUser) {
            $this->deleteHelper->deleteSurvey($user->getDomesticSurvey());
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }
}
