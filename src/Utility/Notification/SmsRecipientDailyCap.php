<?php

namespace App\Utility\Notification;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Counts SMS reservations and repeat attempts per normalised mobile number and London calendar day.
 * Callers must supply numbers in normalised 07 form.
 * Stores only an HMAC of the number. Counter writes join the caller's transaction.
 */
class SmsRecipientDailyCap
{
    private const string LOG_PREFIX = 'Notifications: ';

    public const int DEFAULT_CAP = 5;

    private readonly SmsCapSetting $setting;

    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        int|string|null $cap,
        #[\SensitiveParameter] private readonly string $secret,
    ) {
        $this->setting = SmsCapSetting::parse($cap, self::DEFAULT_CAP);
    }

    /**
     * Returns the Europe/London calendar date containing $now, formatted as Y-m-d.
     */
    public static function day(\DateTimeInterface $now): string
    {
        return \DateTimeImmutable::createFromInterface($now)->setTimezone(new \DateTimeZone('Europe/London'))->format('Y-m-d');
    }

    /**
     * Returns the configured daily cap, or 0 for an invalid configuration.
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
     * Reserves one unit for this number and day and returns true; returns false at the cap or for invalid settings.
     * The update is atomic and joins an existing transaction. Concurrent first inserts can fail with a duplicate key.
     */
    public function tryConsume(string $mobile, \DateTimeInterface $now): bool
    {
        if ($this->setting->invalid) {
            $this->logger->error(self::LOG_PREFIX."APP_SMS_DAILY_CAP_PER_NUMBER invalid: '{$this->setting->raw}' — no SMS will be sent");
            return false;
        }

        $key = $this->hashRecipient($mobile);
        $day = self::day($now);
        $cap = $this->setting->cap;

        $updated = (int) $this->connection->executeStatement(
            'UPDATE sms_recipient_day SET sent = sent + 1 WHERE recipient_hmac = ? AND day = ? AND sent < ?',
            [$key, $day, $cap],
        );
        if ($updated > 0) {
            return true;
        }

        $exists = $this->connection->fetchOne('SELECT 1 FROM sms_recipient_day WHERE recipient_hmac = ? AND day = ?', [$key, $day]) !== false;
        if ($exists || $cap <= 0) {
            return false;
        }
        $this->connection->insert('sms_recipient_day', ['recipient_hmac' => $key, 'day' => $day, 'sent' => 1]);

        return true;
    }

    /**
     * Returns unused units for this number and day, never less than zero.
     * $cap overrides the configured cap for a dry run. Reads without updating the counter.
     */
    public function remaining(string $mobile, \DateTimeInterface $now, ?int $cap = null): int
    {
        $sent = $this->connection->fetchOne('SELECT sent FROM sms_recipient_day WHERE recipient_hmac = ? AND day = ?', [$this->hashRecipient($mobile), self::day($now)]);

        return max(0, ($cap ?? $this->effectiveCap()) - ($sent === false ? 0 : (int) $sent));
    }

    /**
     * Deletes rows before $now's London calendar day and returns the number deleted.
     */
    public function pruneBefore(\DateTimeInterface $now): int
    {
        return (int) $this->connection->executeStatement('DELETE FROM sms_recipient_day WHERE day < ?', [self::day($now)]);
    }

    /**
     * Returns the HMAC-SHA256 identifier for an already normalised mobile number, using APP_SECRET.
     */
    private function hashRecipient(string $mobile): string
    {
        return hash_hmac('sha256', $mobile, $this->secret);
    }
}
