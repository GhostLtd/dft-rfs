<?php

namespace App\Command\Dev;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\PasscodeFormatter;
use App\Utility\UkMobileNumber;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\MessageHandler;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Sends one real test SMS directly through Notify, without queuing or recording a notification.
 * Use the team API key to check delivery; see docs/RemindersRehearsal.md.
 */
#[AsCommand('app:dev:send-test-sms', 'Send ONE real SMS via GOV.UK Notify, to prove SMS delivery (use the team key)')]
class SendTestSmsCommand extends Command
{
    private const string SAMPLE_CODE_1 = '12345678';
    private const string SAMPLE_CODE_2 = '87654321';

    /** The sample codes and loginLink are added in execute(), formatted and built as PersonalisationHelper does. */
    private const array SAMPLE_PERSONALISATION = [
        'surveyPeriodStart' => '1 September 2026',
        'surveyPeriodEnd' => '7 September 2026',
        'surveyPeriod' => '1 September 2026 to 7 September 2026',
        'registrationMark' => 'SD00 PAB',
    ];

    public function __construct(
        private readonly MessageHandler $notifyHandler,
        private readonly PersonalisationHelper $personalisationHelper,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%alphagov_notify_api_key%')]
        private readonly string $apiKey,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('mobile', InputArgument::REQUIRED, 'A UK mobile of a team member of the Notify service (or on its guest list)')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Notify SMS template id', Reference::SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_1)
            ->addOption('survey', null, InputOption::VALUE_REQUIRED, 'Use the personalisation of this CSRGT survey (id) instead of the sample');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mobile = UkMobileNumber::normalise((string) $input->getArgument('mobile'));
        if ($mobile === null) {
            $io->error('That is not a UK mobile number.');
            return Command::FAILURE;
        }

        $personalisation = self::SAMPLE_PERSONALISATION + [
            'passcode1' => PasscodeFormatter::formatPasscode(self::SAMPLE_CODE_1),
            'passcode2' => PasscodeFormatter::formatPasscode(self::SAMPLE_CODE_2),
            'loginLink' => $this->personalisationHelper->getLoginLink(self::SAMPLE_CODE_1),
        ];
        if ($surveyId = $input->getOption('survey')) {
            $survey = $this->entityManager->find(DomesticSurvey::class, $surveyId);
            if (!$survey) {
                $io->error("No CSRGT survey {$surveyId}.");
                return Command::FAILURE;
            }
            // An SMS carries exactly PersonalisationHelper's output (see NotificationSender::createMessages()).
            $personalisation = $this->personalisationHelper->getForEntity($survey);
        }

        $template = (string) $input->getOption('template');
        $keyName = self::extractKeyName($this->apiKey);
        $io->definitionList(['Number' => $mobile], ['Template' => $template], ['Notify key' => $keyName]);
        if (str_contains(strtolower($keyName), 'test')) {
            $io->warning('This looks like a test key: Notify will accept the SMS but deliver nothing. Use the team key.');
        }

        if (!$io->confirm('Send this one SMS now?', false)) {
            $io->writeln('Not sent.');
            return Command::FAILURE;
        }

        try {
            $response = ($this->notifyHandler)(new Sms('test-sms', 'test-sms', 'test-sms', $mobile, $template, $personalisation, 'rfs-test-sms'));
        } catch (\Throwable $e) {
            $io->error('Notify refused it: '.$e->getMessage().($e->getPrevious() ? ' ('.$e->getPrevious()->getMessage().')' : ''));
            return Command::FAILURE;
        }

        $io->success('Sent. Check the phone, and the Notify dashboard.');
        $io->writeln(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
        return Command::SUCCESS;
    }

    /**
     * Returns the display name from a Notify API key, excluding its service ID and secret.
     * Returns "unknown" when the key is too short to contain both UUIDs.
     */
    private static function extractKeyName(string $key): string
    {
        return strlen($key) > 74 ? substr($key, 0, -74) : 'unknown';
    }
}
