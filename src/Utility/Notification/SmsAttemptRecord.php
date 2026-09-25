<?php

namespace App\Utility\Notification;

/**
 * The SMS attempt count and original reservation dates after an attempt has been recorded.
 */
final class SmsAttemptRecord
{
    public function __construct(
        public readonly int $attempts,
        public readonly int $financialYear,
        public readonly ?string $reservedOn, // Y-m-d, Europe/London; null for rows written before the column existed
        public readonly bool $wasMissing,    // no ledger row existed; one was inserted for this attempt
    ) {}
}
