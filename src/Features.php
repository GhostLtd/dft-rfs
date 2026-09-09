<?php

namespace App;

use Ghost\GovUkCoreBundle\Features as CoreFeatures;

class Features extends CoreFeatures
{
    public const string DEV_RORO_AUTO_LOGIN = 'dev-roro-auto-login';
    public const string SMARTLOOK_USER_SESSION_RECORDING = 'smartlook-user-session-recording';
    public const string POSTCODE_CHANGEOVER = 'postcode-changeover';
    public const string OUTWARD_CODE_CHECK = 'outward-code-check';
    // GAE_ENVIRONMENT inherited from CoreFeatures (= 'gae-environment').
}
