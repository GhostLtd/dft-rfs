<?php

namespace App\Command;

use App\Entity\International\Company;
use App\Entity\International\Survey;
use App\Repository\International\CompanyRepository;
use App\Repository\PasscodeUserRepository;
use App\Utility\PasscodeGenerator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
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

    private $appEnvironment;
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
            ->setDescription('Create a new international survey, and passcodes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // TODO: Learn how to reference numbers are actually generated and update accordingly
        $referenceNumber = random_int(10000, 99999);

        // TODO: Use a real company
        /**
         * @var $companyRepo CompanyRepository
         */
        $companyRepo = $this->entityManager->getRepository(Company::class);
        $company = $companyRepo->fetchOrCreateTestCompany();

        $survey = new Survey();
        $survey
            ->setReferenceNumber($referenceNumber)
            ->setSurveyPeriodStart(new DateTime('now +7 days'))
            ->setSurveyPeriodEnd(new DateTime('now +'.(rand(1, 28) + 7).' days'))
            ->setCompany($company)
            ->setPasscodeUser($user = $this->passcodeUserRepository->createNewPasscodeUser())
        ;

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('International survey created');
        $io->writeln("Pass code 1         : {$user->getUsername()}");
        $io->writeln("Pass code 2         : {$user->getPlainPassword()}");
        $io->writeln("Survey period start : {$survey->getSurveyPeriodStart()->format('Y-m-d')}");
        $io->writeln("Survey period end   : {$survey->getSurveyPeriodEnd()->format('Y-m-d')}");
        $io->writeln("");

        return 0;
    }

}
