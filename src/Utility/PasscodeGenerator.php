<?php


namespace App\Utility;


use Exception;

class PasscodeGenerator
{
    /**
     * @throws Exception
     */
    public function generatePasscode()
    {
        while(($passcode = $this->checkPasscode($this->generateUntestedCode())) === false) null;
        return $passcode;
    }

    protected function generateUntestedCode()
    {
        return str_pad(random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
    }

    protected function checkPasscode($passcode)
    {
        if (!$this->isValidPasscode($passcode)) return false;
        return $passcode;
    }

    public function isValidPasscode($passcode, $preventRepeating = 4, $preventSequential = 4)
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

    protected function hasRepeatingDigits($passcode, $preventRepeating = 4)
    {
        return preg_match('/(\d)\1{' . ($preventRepeating - 1) . '}/', $passcode) === 1;
    }

    protected function hasSequentialDigits($passcode, $sequentialCount = 4)
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