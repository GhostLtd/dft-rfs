<?php

namespace App\Utility\Notification;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Counts reserved SMS sends and repeat attempts against the annual allowance, resetting on 1 April in London.
 * Counter writes join the caller's transaction. The attempt ledger lets middleware identify prepaid first attempts.
 */
class SmsAllowance
{
    private const string LOG_PREFIX = 'Notifications: ';

    public const int DEFAULT_ANNUAL_CAP = 20000;

    private readonly SmsCapSetting $setting;

    /**
     * Parses APP_SMS_ANNUAL_CAP once. Invalid values are logged by tryConsume(), avoiding logs on every bus dispatch.
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        int|string|null $cap,
    ) {
        $this->setting = SmsCapSetting::parse($cap, self::DEFAULT_ANNUAL_CAP);
    }

    /**
     * Returns the starting year of the London financial year containing $now.
     */
    public function financialYear(\DateTimeInterface $now): int
    {
        $london = \DateTimeImmutable::createFromInterface($now)->setTimezone(new \DateTimeZone('Europe/London'));
        $year = (int) $london->format('Y');

        return (int) $london->format('n') >= 4 ? $year : $year - 1;
    }

    /**
     * Returns the configured annual cap, or 0 for an invalid configuration.
     */
    public function effectiveCap(): int
    {
        return $this->setting->invalid ? 0 : $this->setting->cap;
    }

    /**
     * Returns whether the configured cap is valid; a configured 0 is valid.
     */
    public function isConfigValid(): bool
    {
        return !$this->setting->invalid;
    }

    /**
     * Returns unused units for the financial year, never less than zero.
     * $cap overrides the configured cap for a dry run. Reads without updating the counter.
     */
    public function remaining(\DateTimeInterface $now, ?int $cap = null): int
    {
        $sent = $this->connection->fetchOne('SELECT sent FROM sms_usage WHERE financial_year = ?', [$this->financialYear($now)]);

        return max(0, ($cap ?? $this->effectiveCap()) - ($sent === false ? 0 : (int) $sent));
    }

    /**
     * Reserves one unit for the financial year and returns true; returns false at the cap or for invalid settings.
     * The update is atomic and joins an existing transaction. Concurrent first inserts can fail with a duplicate key.
     */
    public function tryConsume(\DateTimeInterface $now): bool
    {
        if ($this->setting->invalid) {
            $this->logger->error(self::LOG_PREFIX."APP_SMS_ANNUAL_CAP invalid: '{$this->setting->raw}' — no SMS will be sent");
            return false;
        }

        $year = $this->financialYear($now);

        $updated = (int) $this->connection->executeStatement(
            'UPDATE sms_usage SET sent = sent + 1 WHERE financial_year = ? AND sent < ?',
            [$year, $this->setting->cap],
        );

        if ($updated === 0) {
            $exists = $this->connection->fetchOne('SELECT 1 FROM sms_usage WHERE financial_year = ?', [$year]) !== false;
            if ($exists || $this->setting->cap <= 0) {
                return false;
            }
            $this->connection->insert('sms_usage', ['financial_year' => $year, 'sent' => 1]);
        }

        if ((int) $this->connection->fetchOne('SELECT sent FROM sms_usage WHERE financial_year = ?', [$year]) === $this->setting->cap) {
            $this->logger->warning(self::LOG_PREFIX."SMS allowance for FY {$year} reached ({$this->setting->cap})");
        }

        return true;
    }

    /**
     * Records the year and day prepaid for this notification's first SMS attempt.
     * Called at queue time after both allowances have been reserved.
     */
    public function recordReservation(string $notificationId, \DateTimeInterface $now): void
    {
        $this->connection->insert('sms_attempt', [
            'notification_id' => $notificationId,
            'financial_year' => $this->financialYear($now),
            'reserved_on' => SmsRecipientDailyCap::day($now),
            'attempts' => 0,
        ]);
    }

    /**
     * Increments the attempt count and returns the ledger row, marking a newly inserted row as wasMissing.
     * Charges neither allowance; middleware uses the result to decide what to charge.
     * Runs before Notify is called, outside the survey's queue-time transaction.
     */
    public function recordAttempt(string $notificationId, \DateTimeInterface $now): SmsAttemptRecord
    {
        $this->connection->executeStatement('UPDATE sms_attempt SET attempts = attempts + 1 WHERE notification_id = ?', [$notificationId]);
        $row = $this->connection->fetchAssociative('SELECT attempts, financial_year, reserved_on FROM sms_attempt WHERE notification_id = ?', [$notificationId]);

        if ($row === false) {
            $year = $this->financialYear($now);
            $this->connection->insert('sms_attempt', ['notification_id' => $notificationId, 'financial_year' => $year, 'reserved_on' => null, 'attempts' => 1]);

            return new SmsAttemptRecord(1, $year, null, true);
        }

        return new SmsAttemptRecord(
            (int) $row['attempts'],
            (int) $row['financial_year'],
            $row['reserved_on'] === null ? null : (string) $row['reserved_on'],
            false,
        );
    }
}
