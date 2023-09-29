<?php

namespace App\Command;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Company;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\LongAddress;
use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\RoRoUser;
use App\Entity\Route\Route;
use App\Utility\Domestic\DeleteHelper as DomesticDeleteHelper;
use App\Utility\PreEnquiry\DeleteHelper as PreEnquiryDeleteHelper;
use App\Utility\RoRo\SurveyCreationHelper;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class ScreenshotsCommand extends Command
{
    const MODE_ALL = 'all';
    const MODE_DOMESTIC = 'domestic';
    const MODE_INTERNATIONAL = 'international';
    const MODE_PRE_ENQUIRY = 'pre-enquiry';
    const MODE_RORO = 'roro';

    protected static $defaultName = 'rfs:dev:screenshots';
    protected string $userId = 'screenshot';
    protected string $roroUserId = 'screenshot@example.com';
    protected string $userPassword = 'screenshot:password';

    public function __construct(
        protected EntityManagerInterface    $entityManager,
        protected DomesticDeleteHelper      $domDeleteHelper,
        protected LoginLinkHandlerInterface $roroLoginLinkHandler,
        protected PreEnquiryDeleteHelper    $preEnquiryDeleteHelper,
        protected SurveyCreationHelper      $roroSurveyCreationHelper,
        protected string                    $frontendHostname,
        protected ?string                   $appEnvironment
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Execute the (external) screenshots utility')
            ->addArgument('mode', InputArgument::REQUIRED, 'all|domestic|international|pre-enquiry|roro')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to the screenshots command')
            ->addOption('protocol', null, InputOption::VALUE_OPTIONAL, 'http or https', 'https');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->appEnvironment !== 'staging') {
            throw new RuntimeException('must be running in "staging" app environment');
        }

        $io = new SymfonyStyle($input, $output);
        $io->warning('If taking screenshots for documentation, check that code is at same commit as deployed on PRODUCTION');

        $mode = $input->getArgument('mode');
        $path = $input->getArgument('path');

        if (!in_array($mode, [self::MODE_DOMESTIC, self::MODE_INTERNATIONAL, self::MODE_PRE_ENQUIRY, self::MODE_RORO, self::MODE_ALL])) {
            throw new RuntimeException('mode must be either all, domestic, international, pre-enquiry or roro');
        }

        $modes = ($mode === self::MODE_ALL) ?
            [self::MODE_DOMESTIC, self::MODE_INTERNATIONAL, self::MODE_PRE_ENQUIRY, self::MODE_RORO] :
            [$mode];

        $this->deleteExistingUser($modes);

        $outputBaseDir = dirname($path) . "/screenshots-" . (new \DateTime())->format('Ymd-His');

        foreach ($modes as $activeMode) {
            $this->createUserAndSurvey($activeMode);


            $protocol = $input->getOption('protocol');
            $processArgs = [
                $path,
                $activeMode,
                "{$protocol}:/{$this->frontendHostname}/",
                "{$outputBaseDir}/{$activeMode}/",
            ];

            if ($activeMode === self::MODE_RORO) {
                $roroUser = $this->getRoroUser();
                $loginLink = $this->roroLoginLinkHandler->createLoginLink($roroUser);

                $processArgs[] = "--email={$this->roroUserId}";
                $processArgs[] = "--login-link={$loginLink}";
            } else {
                $processArgs[] = "--username={$this->userId}";
                $processArgs[] = "--password={$this->userPassword}";
            }

            $process = new Process($processArgs);
            $process->setTimeout(3600);

            try {
                $process->mustRun();

                echo $process->getOutput();
            } catch (ProcessFailedException $exception) {
                echo $exception->getMessage();
            }

            $this->deleteExistingUser($modes);
        }

        return 0;
    }

    /** @param array<string> $modes */
    protected function deleteExistingUser(array $modes): void
    {
        $this->entityManager->clear();

        if ($this->isDomesticInternationOrPreEnquiry($modes)) {
            $userRepo = $this->entityManager->getRepository(PasscodeUser::class);
            $user = $userRepo->findOneBy(['username' => $this->userId]);

            if ($user instanceof PasscodeUser) {
                $domesticSurvey = $user->getDomesticSurvey();
                $internationSurvey = $user->getInternationalSurvey();
                $preEnquiry = $user->getPreEnquiry();

                $domesticSurvey && $this->domDeleteHelper->deleteSurvey($domesticSurvey);

                if ($internationSurvey) {
                    $company = $internationSurvey->getCompany();
                    $this->entityManager->remove($internationSurvey);
                    $this->entityManager->remove($company);
                }

                if ($preEnquiry) {
                    $this->preEnquiryDeleteHelper->deletePreEnquiry($preEnquiry);
                }

                $this->entityManager->remove($user);
                $this->entityManager->flush();
            }
        }

        if (in_array(self::MODE_RORO, $modes)) {
            $roroUser = $this->getRoroUser();

            if ($roroUser instanceof RoRoUser) {
                $operator = $roroUser->getOperator();

                $surveys = $this->entityManager
                    ->getRepository(Survey::class)
                    ->getAllSurveysForOperator($operator);

                foreach ($surveys as $survey) {
                    $this->entityManager->remove($survey);
                }


                $this->entityManager->remove($roroUser);
                $this->entityManager->remove($operator);
                $this->entityManager->flush();
            }
        }
    }

    protected function createUserAndSurvey(string $mode): void
    {
        if ($this->isDomesticInternationOrPreEnquiry([$mode])) {
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
                $invitationAddress = (new LongAddress())
                    ->setLine1('Screenshot Tests Ltd')
                    ->setLine2('123 Fictional Road')
                    ->setLine3('Towntown')
                    ->setLine4('West Countyshire')
                    ->setPostcode('W01 1AB');

                $survey = (new PreEnquiry())
                    ->setInvitationAddress($invitationAddress)
                    ->setCompanyName('Screenshot Tests Ltd')
                    ->setPasscodeUser($user)
                    ->setReferenceNumber('screenshots-test');
            }

            $this->entityManager->persist($survey);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        if ($mode === self::MODE_RORO) {
            $operator = (new Operator())
                ->setName('Test operator')
                ->setCode(999)
                ->setIsActive(true);

            $route = $this->entityManager
                ->getRepository(Route::class)
                ->getRouteByPortNames('Dover', 'Calais');


            $user = (new RoRoUser())
                ->setOperator($operator)
                ->setUsername($this->roroUserId);

            $this->entityManager->persist($operator);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $survey = $this->roroSurveyCreationHelper->createSurvey(
                new DateTime(),
                new OperatorRoute($operator->getId(), $route->getId())
            );

            $this->entityManager->persist($survey);
            $this->entityManager->flush();
        }
    }

    protected function isDomesticInternationOrPreEnquiry(array|string $modeOrModes): bool
    {
        if (is_array($modeOrModes)) {
            return
                in_array(self::MODE_DOMESTIC, $modeOrModes) ||
                in_array(self::MODE_INTERNATIONAL, $modeOrModes) ||
                in_array(self::MODE_PRE_ENQUIRY, $modeOrModes);
        } else {
            return in_array($modeOrModes, [self::MODE_DOMESTIC, self::MODE_INTERNATIONAL, self::MODE_PRE_ENQUIRY]);
        }
    }

    public function getRoroUser(): ?RoRoUser
    {
        $roroRepo = $this->entityManager->getRepository(RoRoUser::class);
        return $roroRepo->findOneBy(['username' => $this->roroUserId]);
    }
}
