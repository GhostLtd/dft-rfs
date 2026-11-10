<?php

namespace App\Messenger;

use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

/**
 * An SMS attempt refused by our own caps (annual allowance or per-number daily cap). Never retried.
 *
 * Recorded as "refused" rather than "failed": the number was never tried, so the refusal says nothing about whether
 * it's good or bad, and isn't a delivery problem.
 */
class SmsCapRefusedException extends UnrecoverableMessageHandlingException
{
}
