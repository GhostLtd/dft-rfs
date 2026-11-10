<?php

namespace App\Utility\AlphagovNotify\Callback;

/**
 * A callback payload we can't make sense of (answered with a 400, which Notify doesn't send again)
 */
class InvalidCallbackException extends \RuntimeException
{
}
