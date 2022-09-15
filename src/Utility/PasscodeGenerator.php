<?php

namespace App\Utility;

use App\Entity\PasscodeUser;
use App\Repository\PasscodeUserRepository;

class PasscodeGenerator
{
    protected ?PasscodeUserRepository $passcodeUserRepository;
    protected string $secret;

    public function __construct(?PasscodeUserRepository $passcodeUserRepository, string $secret)
    {
        $this->passcodeUserRepository = $passcodeUserRepository;
        $this->secret = $secret;
    }

    public function createNewPasscodeUser(): PasscodeUser
    {
        return (new PasscodeUser())
            ->setUsername($this->generateUsernameCode())
            ->setPassword(null);
    }

    public function getPasswordForUser(PasscodeUser $user): string
    {
        $hash = hash('sha256', $this->secret.$user->getId());
        $passcode = hexdec(substr($hash, -8, 8));
        return str_pad($passcode % 100000000, 8, '0', STR_PAD_LEFT);
    }

    public function generateUsernameCode(): string
    {
        while(($passcode = $this->checkPasscode($this->generateUntestedCode(), true)) === null) {};
        return $passcode;
    }

    protected function generateUntestedCode(): string
    {
        return str_pad(random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
    }

    protected function checkPasscode($passcode, $enforceUniqueUsername = false): ?string
    {
        if (!$this->isValidPasscode($passcode)) {
            return null;
        }

        if ($enforceUniqueUsername) {
            if ($this->usernameExistsInDatabase($passcode)) {
                return null;
            }
        }

        return $passcode;
    }

    public function isValidPasscode($passcode, $preventRepeating = 4, $preventSequential = 4): bool
    {
        if (strlen($passcode) !== 8) {
            return false;
        }

        // reject if 4 consecutive numbers same
        if ($this->hasRepeatingDigits($passcode, $preventRepeating)) {
            return false;
        }

        // test for sequential
        if ($this->hasSequentialDigits($passcode, $preventSequential)) {
            return false;
        }

        return true;
    }

    protected function usernameExistsInDatabase($passcode): bool
    {
        $result = $this->passcodeUserRepository->findBy(['username' => $passcode]);
        return (!empty($result));
    }

    protected function hasRepeatingDigits($passcode, $preventRepeating = 4): bool
    {
        return preg_match('/(\d)\1{' . ($preventRepeating - 1) . '}/', $passcode) === 1;
    }

    protected function hasSequentialDigits($passcode, $sequentialCount = 4): bool
    {
        $positionLimit = strlen($passcode) - $sequentialCount;
        if ($positionLimit < 0) {
            return false;
        }

        for($i=0; $i<=$positionLimit; $i++) {
            $number = substr($passcode, $i, $sequentialCount);
            $up = $down = true;
            $current = intval($number[0]);

            for($j=1; $j<$sequentialCount; $j++) {
                $next = intval($number[$j]);
                $up &= ($next === ($current + 1) % 10);
                $down &= ($next === ($current - 1) % 10);
                $current = $next;
            }

            if ($up || $down) {
                return true;
            }
        }

        return false;
    }
}