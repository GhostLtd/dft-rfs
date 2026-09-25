<?php

namespace App\Utility\Notification;

/**
 * Parses an SMS cap without throwing: empty uses the default, valid values are non-negative integers.
 * Invalid values are marked invalid and have an effective cap of 0.
 */
final class SmsCapSetting
{
    private function __construct(
        public readonly int $cap,
        public readonly bool $invalid,
        public readonly string $raw,
    ) {}

    /**
     * Returns the parsed cap, its validity, and the original value for error logging.
     */
    public static function parse(int|string|null $value, int $default): self
    {
        $raw = is_string($value) ? $value : (string) ($value ?? '');
        $trimmed = is_string($value) ? trim($value) : $value;

        if ($trimmed === null || $trimmed === '') {
            return new self($default, false, $raw);
        }
        if ((is_int($trimmed) && $trimmed >= 0) || (is_string($trimmed) && preg_match('/^\d+$/', $trimmed) === 1)) {
            return new self((int) $trimmed, false, $raw);
        }

        return new self(0, true, $raw);
    }
}
