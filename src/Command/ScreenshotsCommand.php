<?php

namespace App\Command;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Company;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\LongAddress;
use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Repository\PasscodeUserRepository;
use App\Utility\Domestic\DeleteHelper as DomesticDeleteHelper;
use App\Utility\International\DeleteHelper as InternationalDeleteHelper;
use App\Utility\PreEnquiry\DeleteHelper as PreEnquiryDeleteHelper;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ScreenshotsCommand extends Command
{
    const MODE_DOMESTIC = 'domestic';
    const MODE_INTERNATIONAL = 'international';
    const MODE_PRE_ENQUIRY = 'pre-enquiry';

    protected static $defaultName = 'rfs:dev:screenshots';

    protected EntityManagerInterface $entityManager;
    protected DomesticDeleteHelper $domDeleteHelper;
    protected InternationalDeleteHelper $intDeleteHelper;
    protected PreEnquiryDeleteHelper $preEnquiryDeleteHelper;

    protected string $frontendHostname;
    protected string $userId = 'screenshot';
    protected string $userPassword = 'screenshot:password';
    private ?string $appEnvironment;

    public function __construct(EntityManagerInterface $entityManager, DomesticDeleteHelper $domDeleteHelper, InternationalDeleteHelper $intDeleteHelper, PreEnquiryDeleteHelper $preEnquiryDeleteHelper, string $frontendHostname, ?string $appEnvironment)
    {
        parent::__construct();
        $this->appEnvironment = $appEnvironment;
        $this->entityManager = $entityManager;
        $this->domDeleteHelper = $domDeleteHelper;
        $this->frontendHostname = $frontendHostname;
        $this->intDeleteHelper = $intDeleteHelper;
        $this->preEnquiryDeleteHelper = $preEnquiryDeleteHelper;
    }

    protected function configure()
    {
        $this->setDescription('Execute the (external) screenshots utility')
            ->addArgument('mode', InputArgument::REQUIRED, 'domestic|international|pre-enquiry')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to the screenshots command')
            ->addOption('protocol', null, InputOption::VALUE_OPTIONAL, 'http or https', 'https');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->appEnvironment !== 'staging') {
            throw new RuntimeException('must be running in "staging" app environment');
        }

        $mode = $input->getArgument('mode');

        if (!in_array($mode, [self::MODE_DOMESTIC, self::MODE_INTERNATIONAL, self::MODE_PRE_ENQUIRY])) {
            throw new RuntimeException('mode must be either domestic, international or pre-enquiry');
        }

        $this->deleteExistingUser();

        $user = (new PasscodeUser())
            ->setUsername($this->userId)
            ->setPlainPassword($this->userPassword);

        $start = new DateTime();
        $end = (clone $start)->add(new DateInterval('P6D'));

        if ($mode === self::MODE_DOMESTIC) {
            $survey = (new DomesticSurvey())
                ->setRegistrationMark('TE01 STT')
                ->setIsNorthernIreland(true)
                ->setSurveyPeriodStart($start)
                ->setSurveyPeriodEnd($end)
                ->setPasscodeUser($user)
                ->setInvitationAddress(new LongAddress());
        } else if ($mode === self::MODE_INTERNATIONAL) {
            $company = (new Company())
                ->setBusinessName('Screenshot Tests Ltd');

            $this->entityManager->persist($company);

            $survey = (new InternationalSurvey())
                ->setCompany($company)
                ->setSurveyPeriodStart($start)
                ->setSurveyPeriodEnd($end)
                ->setPasscodeUser($user)
                ->setReferenceNumber('screenshots-test')
                ->setInvitationAddress(new LongAddress());
        } else {
            $company = (new Company())
                ->setBusinessName('Screenshot Tests Ltd');

            $this->entityManager->persist($company);

            $invitationAddress = (new LongAddress())
                ->setLine1('Screenshot Tests Ltd')
                ->setLine2('123 Fictional Road')
                ->setLine3('Towntown')
                ->setLine4('West Countyshire')
                ->setPostcode('W01 1AB');

            $survey = (new PreEnquiry())
                ->setInvitationAddress($invitationAddress)
                ->setCompany($company)
                ->setPasscodeUser($user)
                ->setReferenceNumber('screenshots-test');
        }

        $this->entityManager->persist($survey);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $protocol = $input->getOption('protocol');
        $process = new Process([$input->getArgument('path'), $mode, "{$protocol}:/{$this->frontendHostname}/", "--username={$this->userId}", "--password={$this->userPassword}"]);
        $process->setTimeout(3600);

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
            $domesticSurvey = $user->getDomesticSurvey();
            $internationSurvey = $user->getInternationalSurvey();
            $preEnquiry = $user->getPreEnquiry();

            $domesticSurvey && $this->domDeleteHelper->deleteSurvey($domesticSurvey);

            if ($internationSurvey) {
                $company = $internationSurvey->getCompany();
                $this->intDeleteHelper->deleteSurvey($internationSurvey);
                $this->entityManager->remove($company);
            }

            if ($preEnquiry) {
                $company = $preEnquiry->getCompany();
                $this->preEnquiryDeleteHelper->deletePreEnquiry($preEnquiry);
                $this->entityManager->remove($company);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }
}
