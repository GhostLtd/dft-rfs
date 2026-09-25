<?php

namespace App\Utility\Reminder\DryRun;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Embeddable\LongAddress;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Utility\DatabaseDescription;
use App\Utility\Notification\NotificationSender;
use App\Utility\Notification\NotificationType;
use App\Utility\Notification\SmsAllowance;
use App\Utility\Notification\SmsRecipientDailyCap;
use App\Utility\RegistrationMarkHelper;
use App\Utility\Reminder\AutomatedRemindersInterface;
use App\Utility\Reminder\DisabledRemindersHelper;
use App\Utility\Reminder\DomesticInProgressReminders;
use App\Utility\Reminder\DueItem;
use App\Utility\Reminder\RoRoReminders;
use Doctrine\DBAL\Connection;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\AbstractMessage;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prints the messages each reminder family would queue, plus skip reasons and SMS totals.
 * Uses the real selection and message-building code. Never dispatches, and always rolls back its transaction.
 * Daily counts from a database written with a different APP_SECRET cannot be matched.
 */
class ReminderDryRun
{
    public function __construct(
        private readonly Connection $connection,
        private readonly NotificationSender $sender,
        private readonly SmsAllowance $smsAllowance,
        private readonly SmsRecipientDailyCap $dailyCap,
        private readonly DisabledRemindersHelper $disabledRemindersHelper,
        private readonly DatabaseDescription $databaseDescription,
    ) {}

    /**
     * Prints due reminders and simulates both SMS budgets, returning the console exit code.
     *
     * @param iterable<AutomatedRemindersInterface> $families
     */
    public function run(iterable $families, DryRunOptions $options, SymfonyStyle $io): int
    {
        $resolved = [];
        $fromShown = '(n/a)';
        foreach ($families as $family) {
            if ($family instanceof DomesticInProgressReminders) {
                $family = $options->from !== null ? $family->withRemindersFrom($options->from) : $family;
                $fromShown = $family->getRemindersFromSetting() ?? '(unset)';
            }
            $resolved[] = $family;
        }

        $disabled = $options->disabled ?? $this->disabledRemindersHelper->getDisabled();
        $cap = $options->smsCap ?? $this->smsAllowance->effectiveCap();
        $perNumberCap = $options->smsDailyCap ?? $this->dailyCap->effectiveCap();

        $io->writeln(sprintf('Effective settings: APP_IN_PROGRESS_REMINDERS_FROM=%s  APP_DISABLE_REMINDERS=%s  APP_SMS_ANNUAL_CAP=%d  APP_SMS_DAILY_CAP_PER_NUMBER=%d',
            $fromShown, $disabled === [] ? '(none)' : implode(',', $disabled), $cap, $perNumberCap));
        $io->writeln(sprintf('Database: %s   Evaluated at: %s Europe/London',
            $this->databaseDescription->describe(),
            $options->at->setTimezone(new \DateTimeZone('Europe/London'))->format('Y-m-d H:i')));

        $this->connection->beginTransaction();
        try {
            $budget = $this->smsAllowance->remaining($options->at, $cap);
            $used = max(0, $cap - $budget);
            $smsWould = 0;
            /** @var array<string, int> $smsSkipped */
            $smsSkipped = [];
            /** @var array<string, int> $perNumber units left today, by mobile, seeded from the database on first use */
            $perNumber = [];
            // Per-number first, then annual, as NotificationSender::send() charges them.
            $consume = function (string $mobile) use (&$budget, &$perNumber, $perNumberCap, $options): ?string {
                $perNumber[$mobile] ??= $this->dailyCap->remaining($mobile, $options->at, $perNumberCap);
                if ($perNumber[$mobile] <= 0) {
                    return 'SMS per-number daily cap reached';
                }
                $perNumber[$mobile]--;
                if ($budget <= 0) {
                    return 'SMS allowance exhausted';
                }
                $budget--;
                return null;
            };

            foreach ($resolved as $family) {
                $io->newLine();
                $io->writeln($family->getFamily());
                if (in_array($family->getFamily(), $disabled, true)) {
                    $io->writeln('  disabled (APP_DISABLE_REMINDERS)');
                    continue;
                }

                $due = $family->due($options->at);
                foreach ($due->notes as $note) {
                    $io->writeln("  nothing sent: {$note}");
                }

                foreach ($due->types as $type) {
                    $items = $due->ofType($type);
                    $io->writeln(sprintf('  %s: %d due', $type->value, array_sum(array_map(fn(DueItem $i) => count($i->surveys), $items))));

                    $lines = [];
                    foreach ($items as $item) {
                        $lines[] = $this->describeReminder($family, $type, $item, $consume, $smsWould, $smsSkipped);
                    }
                    $this->writeLines($io, $lines, $options);
                }

                if ($due->heldBack !== []) {
                    $io->writeln(sprintf('  held back (contacted in last 7 days): %d', count($due->heldBack)));
                    $lines = array_map(fn($h) => sprintf('%s  %s  last contact %s (%s)', $this->surveyLabel($h->survey), $h->type->value,
                        $h->latestContact?->format('Y-m-d H:i') ?? '?', $h->event ?? '?'), $due->heldBack);
                    $this->writeLines($io, $lines, $options);
                }
            }

            $io->newLine();
            ksort($smsSkipped);
            $io->writeln(sprintf('SMS: %d would send, %d skipped%s; allowance FY%d: %d used, %d cap → %d left after this run',
                $smsWould,
                array_sum($smsSkipped),
                $smsSkipped === [] ? '' : ' ('.implode(', ', array_map(fn($r, $n) => "{$r}: {$n}", array_keys($smsSkipped), $smsSkipped)).')',
                $this->smsAllowance->financialYear($options->at),
                $used,
                $cap,
                $budget,
            ));
        } finally {
            $this->connection->rollBack();
        }

        return Command::SUCCESS;
    }

    /**
     * Returns one output line listing recipients and skipped channels. Updates the simulated SMS totals.
     *
     * @param array<string, int> $smsSkipped
     */
    private function describeReminder(AutomatedRemindersInterface $family, NotificationType $type, DueItem $item, \Closure $consume, int &$smsWould, array &$smsSkipped): string
    {
        if ($family instanceof RoRoReminders) {
            $emails = $family->buildDigest($item->surveys, $type);
            $operator = $item->surveys[0]->getOperator();
            return sprintf('operator %s (%d routes)  %s', $operator->getName(), count($item->surveys),
                implode('   ', array_map(fn(Email $e) => 'email '.$this->formatRecipient($e), $emails)) ?: 'email skipped: no users');
        }

        $preview = $this->sender->preview($item->surveys[0], $type, $consume);
        $parts = [];
        foreach ($preview['messages'] as $message) {
            if ($message instanceof Sms) {
                $smsWould++;
            }
            $parts[] = $this->channelName($message).' '.$this->formatRecipient($message);
        }
        foreach ($preview['skipped'] as ['channel' => $channel, 'reason' => $reason]) {
            if ($channel->value === 'sms') {
                $smsSkipped[$reason] = ($smsSkipped[$reason] ?? 0) + 1;
            }
            $parts[] = "{$channel->value} skipped: {$reason}";
        }

        return $this->surveyLabel($item->surveys[0]).'  '.implode('   ', $parts);
    }

    /**
     * Prints detail lines subject to --limit and --summary, followed by the count omitted.
     *
     * @param list<string> $lines
     */
    private function writeLines(SymfonyStyle $io, array $lines, DryRunOptions $options): void
    {
        $shown = $options->summary ? [] : ($options->limit === null ? $lines : array_slice($lines, 0, $options->limit));
        foreach ($shown as $line) {
            $io->writeln("    {$line}");
        }
        if (count($shown) < count($lines)) {
            $io->writeln(sprintf('    ...  (%d more; --limit / drop --summary to list)', count($lines) - count($shown)));
        }
    }

    /**
     * Returns the registration or reference number followed by the survey ID.
     */
    private function surveyLabel(object $survey): string
    {
        $name = match (true) {
            $survey instanceof DomesticSurvey => (new RegistrationMarkHelper($survey->getRegistrationMark()))->getFormattedRegistrationMark() ?: (string) $survey->getRegistrationMark(),
            $survey instanceof InternationalSurvey, $survey instanceof PreEnquiry => (string) $survey->getReferenceNumber(),
            $survey instanceof RoRoSurvey => 'RoRo',
            default => '',
        };

        return trim($name.'  (survey '.$survey->getId().')');
    }

    /**
     * Returns the message's channel name for display.
     */
    private function channelName(AbstractMessage $message): string
    {
        return match (true) {
            $message instanceof Letter => 'letter',
            $message instanceof Email => 'email',
            $message instanceof Sms => 'sms',
            default => 'message',
        };
    }

    /**
     * Returns the email address, mobile number or postal address for display.
     */
    private function formatRecipient(AbstractMessage $message): string
    {
        return match (true) {
            $message instanceof Email => $message->getEmailAddress(),
            $message instanceof Sms => $message->getPhoneNumber(),
            $message instanceof Letter => $this->formatAddress($message->getAddress()),
            default => '?',
        };
    }

    /**
     * Returns a comma-separated postal address, or "?" for an unsupported address object.
     */
    private function formatAddress(object $address): string
    {
        if (!$address instanceof LongAddress) {
            return '?';
        }
        return implode(', ', array_filter($address->toArray()));
    }
}
