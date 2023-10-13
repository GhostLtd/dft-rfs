<?php

namespace App\Utility\Reminder;

class DisabledRemindersHelper
{
    public const CSRGT = 'csrgt';
    public const IRHS = 'irhs';
    public const PRE_ENQUIRY = 'pre-enquiry';
    public const RORO = 'roro';

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
}