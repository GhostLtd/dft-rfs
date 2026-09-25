<?php

namespace App\Utility\Reminder;

class DisabledRemindersHelper
{
    public const CSRGT = 'csrgt';
    public const IRHS = 'irhs';
    public const PRE_ENQUIRY = 'pre-enquiry';
    public const RORO = 'roro';
    public const CSRGT_IN_PROGRESS = 'csrgt-in-progress';

    protected array $disabledReminders;

    public function __construct(?string $disableReminders)
    {
        $lowercaseTrimmedReminders = array_map(
            fn(string $x) => strtolower(trim($x)),
            explode(',', $disableReminders ?? '')
        );

        $this->disabledReminders = array_filter($lowercaseTrimmedReminders, fn(string $x) => $x !== '');
    }

    public function isDisabled(string $type): bool
    {
        return in_array($type, $this->disabledReminders);
    }

    /** @return list<string> */
    public function getDisabled(): array
    {
        return array_values($this->disabledReminders);
    }
}