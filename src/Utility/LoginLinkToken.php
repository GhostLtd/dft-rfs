<?php

namespace App\Utility;

/**
 * Reversibly encodes the eight-digit passcode username as a five-character base-62 login-link token.
 * The token contains code 1 only; code 2 remains required to log in.
 */
class LoginLinkToken
{
    private const string ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    private const int LENGTH = 5;
    private const int CODE_LIMIT = 100_000_000;

    /**
     * Returns the five-character token for an eight-digit code 1. Throws InvalidArgumentException for invalid input.
     */
    public static function encode(string $code1): string
    {
        if (preg_match('/^\d{8}$/', $code1) !== 1) {
            throw new \InvalidArgumentException('Code 1 must be 8 digits');
        }

        $value = (int) $code1;
        $token = '';
        do {
            $token = self::ALPHABET[$value % 62].$token;
            $value = intdiv($value, 62);
        } while ($value > 0);

        return str_pad($token, self::LENGTH, self::ALPHABET[0], STR_PAD_LEFT);
    }

    /**
     * Returns the eight-digit code 1, or null for an invalid token or a value outside the eight-digit range.
     */
    public static function decode(string $token): ?string
    {
        if (preg_match('/^[0-9A-Za-z]{'.self::LENGTH.'}$/', $token) !== 1) {
            return null;
        }

        $value = 0;
        foreach (str_split($token) as $character) {
            $value = $value * 62 + strpos(self::ALPHABET, $character);
        }

        return $value < self::CODE_LIMIT ? str_pad((string) $value, 8, '0', STR_PAD_LEFT) : null;
    }
}
