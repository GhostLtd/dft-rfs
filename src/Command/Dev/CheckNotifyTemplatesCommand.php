<?php

namespace App\Command\Dev;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception\ApiException;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Utility\Notification\NotificationSender;
use App\Utility\Notification\NotificationTemplates;
use App\Utility\Rehearsal\SeedManifestStore;
use App\Utility\Reminder\RoRoReminders;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\AbstractMessage;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Checks each Notify template using message personalisation built from seeded rehearsal surveys.
 * Calls Notify's preview endpoint without sending messages.
 */
#[AsCommand('app:dev:check-notify-templates', "Preview every Notify template with the personalisation we send (sends nothing; needs a seed)")]
class CheckNotifyTemplatesCommand extends Command
{
    /** @var array<class-string, string> distinct display labels for survey classes */
    private const array CLASS_LABELS = [
        DomesticSurvey::class => 'CSRGT',
        InternationalSurvey::class => 'IRHS',
        PreEnquiry::class => 'Pre-enquiry',
    ];

    public function __construct(
        private readonly SeedManifestStore $manifestStore,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationSender $sender,
        private readonly RoRoReminders $roroReminders,
        private readonly Client $notifyClient,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifest = $this->manifestStore->find();
        if ($manifest === null) {
            $io->error('No seed found. Seed first: app:dev:seed-reminder-scenarios (the seeded surveys are the samples).');
            return Command::FAILURE;
        }

        $rows = [];
        $failed = 0;
        $check = function (string $what, string $templateId, ?AbstractMessage $message) use (&$rows, &$failed): void {
            if ($message === null) {
                $rows[] = ['❌', $what, $templateId, 'no seeded survey can receive this channel'];
                $failed++;
                return;
            }
            try {
                $this->notifyClient->previewTemplate($templateId, $message->getPersonalisation());
                $rows[] = ['✅', $what, $templateId, ''];
            } catch (ApiException $e) {
                // ApiException::getErrors() is declared to return ResponseInterface in its docblock (a bug in the
                // vendor library); it actually returns the decoded error list, so cast rather than trust the type.
                $errors = (array) $e->getErrors();
                $rows[] = ['❌', $what, $templateId, implode('; ', array_map(fn(array $err) => "{$err['error']}: {$err['message']}", $errors))];
                $failed++;
            } catch (\Throwable $e) {
                $rows[] = ['❌', $what, $templateId, $e->getMessage()];
                $failed++;
            }
        };

        foreach ([DomesticSurvey::class, InternationalSurvey::class, PreEnquiry::class] as $class) {
            $surveys = $this->entityManager->getRepository($class)->findBy(['id' => $manifest->surveyIds($class)]);
            foreach (NotificationTemplates::typesFor($class) as $type) {
                $samples = [];
                foreach ($surveys as $survey) {
                    // Allow every SMS: a preview charges neither the per-number cap nor the annual allowance.
                    foreach ($this->sender->preview($survey, $type, fn(string $mobile): ?string => null)['messages'] as $message) {
                        $samples[$this->channelName($message)] ??= $message;
                    }
                }
                foreach (NotificationTemplates::for($class, $type) as $channel => $templateId) {
                    $check(self::CLASS_LABELS[$class]." {$type->value} {$channel}", $templateId, $samples[$channel] ?? null);
                }
            }
        }

        // All seeded RoRo surveys share one operator, so the first digest is a representative sample.
        $roroSurveys = $this->entityManager->getRepository(RoRoSurvey::class)->findBy(['id' => $manifest->surveyIds(RoRoSurvey::class)]);
        foreach (NotificationTemplates::typesFor(RoRoSurvey::class) as $type) {
            $emails = $roroSurveys === [] ? [] : $this->roroReminders->buildDigest(array_values($roroSurveys), $type);
            foreach (NotificationTemplates::for(RoRoSurvey::class, $type) as $channel => $templateId) {
                $check("RoRo {$type->value} {$channel}", $templateId, $emails[0] ?? null);
            }
        }

        $io->table(['', 'template', 'id', 'problem'], $rows);
        if ($failed > 0) {
            $io->error("{$failed} template(s) failed. Fix the template in Notify, or what we send, before going further.");
            return Command::FAILURE;
        }
        $io->success('Every template accepts the personalisation we send.');
        return Command::SUCCESS;
    }

    /**
     * Returns the channel name for a Notify message, or "unknown" for an unsupported type.
     */
    private function channelName(AbstractMessage $message): string
    {
        return match (true) {
            $message instanceof Letter => 'letter',
            $message instanceof Email => 'email',
            $message instanceof Sms => 'sms',
            default => 'unknown',
        };
    }
}
