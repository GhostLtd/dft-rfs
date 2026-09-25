<?php

namespace App\Command\Cron;

use App\Utility\Reminder\DryRun\DryRunOptions;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/** The --dry-run option set shared by the two reminder commands. */
trait DryRunOptionsTrait
{
    private const array DRY_RUN_ONLY = ['at', 'from', 'disabled', 'sms-cap', 'sms-daily-cap', 'limit', 'summary'];

    private function configureDryRunOptions(bool $withFrom): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List what would be sent, without sending or writing anything')
            ->addOption('at', null, InputOption::VALUE_REQUIRED, 'Dry run: evaluate as if run at this time, Europe/London (e.g. "2026-10-05 09:00")')
            ->addOption('disabled', null, InputOption::VALUE_REQUIRED, 'Dry run: the effective APP_DISABLE_REMINDERS (comma-separated; "--disabled=" for none)')
            ->addOption('sms-cap', null, InputOption::VALUE_REQUIRED, 'Dry run: the effective APP_SMS_ANNUAL_CAP')
            ->addOption('sms-daily-cap', null, InputOption::VALUE_REQUIRED, 'Dry run: the effective APP_SMS_DAILY_CAP_PER_NUMBER')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Dry run: show at most N rows per type (totals are unaffected)')
            ->addOption('summary', null, InputOption::VALUE_NONE, 'Dry run: show counts only');
        if ($withFrom) {
            $this->addOption('from', null, InputOption::VALUE_REQUIRED, 'Dry run: the effective APP_IN_PROGRESS_REMINDERS_FROM (Y-m-d)');
        }
    }

    /**
     * Returns an error when a dry-run-only option is used without --dry-run; otherwise null.
     */
    private function dryRunMisuse(InputInterface $input): ?string
    {
        if ($input->getOption('dry-run')) {
            return null;
        }
        foreach (self::DRY_RUN_ONLY as $name) {
            if ($input->hasOption($name) && !in_array($input->getOption($name), [null, false], true)) {
                return "--{$name} is only valid with --dry-run";
            }
        }
        return null;
    }

    /**
     * Returns the first invalid date or cap option error, or null when the values are valid.
     */
    private function dryRunOptionsError(InputInterface $input): ?string
    {
        $cap = $input->getOption('sms-cap');
        if ($cap !== null && !preg_match('/^\d+$/', trim((string) $cap))) {
            return '--sms-cap must be a whole number';
        }

        $dailyCap = $input->getOption('sms-daily-cap');
        if ($dailyCap !== null && !preg_match('/^\d+$/', trim((string) $dailyCap))) {
            return '--sms-daily-cap must be a whole number';
        }

        $at = $input->getOption('at');
        if ($at !== null) {
            try {
                new \DateTimeImmutable($at, new \DateTimeZone('Europe/London'));
            } catch (\Exception) {
                return "--at could not be parsed as a date/time, e.g. \"2026-10-05 09:00\"";
            }
        }

        return null;
    }

    /**
     * Returns the parsed dry-run options, using the clock for an omitted --at value.
     */
    private function dryRunOptions(InputInterface $input, ClockInterface $clock): DryRunOptions
    {
        $at = $input->getOption('at');
        $disabled = $input->getOption('disabled');
        $cap = $input->getOption('sms-cap');
        $dailyCap = $input->getOption('sms-daily-cap');
        $limit = $input->getOption('limit');

        return new DryRunOptions(
            $at === null ? \DateTimeImmutable::createFromInterface($clock->now()) : new \DateTimeImmutable($at, new \DateTimeZone('Europe/London')),
            $input->hasOption('from') ? $input->getOption('from') : null,
            $disabled === null ? null : array_values(array_filter(array_map(fn($s) => strtolower(trim($s)), explode(',', $disabled)))),
            $cap === null ? null : (int) $cap,
            $dailyCap === null ? null : (int) $dailyCap,
            $limit === null ? null : max(0, (int) $limit),
            (bool) $input->getOption('summary'),
        );
    }
}
