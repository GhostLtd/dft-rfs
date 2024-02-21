<?php

namespace App\Utility;

class PasscodeFormatter
{
    public static function formatPasscode(string $passcode): string
    {
        return join('-', str_split($passcode, 4));
    }
}
