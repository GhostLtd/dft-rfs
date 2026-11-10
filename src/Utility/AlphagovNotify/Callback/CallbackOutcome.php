<?php

namespace App\Utility\AlphagovNotify\Callback;

enum CallbackOutcome
{
    // Matched one of our responses and stored
    case STORED;
    // Matched, but already stored (Notify sent it again)
    case DUPLICATE;
    // Not one of ours (e.g. sent from another environment sharing the Notify service): not stored
    case UNMATCHED;
}
