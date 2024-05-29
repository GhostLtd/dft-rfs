<?php

namespace App\Utility\Quarter;

use App\Utility\Domestic\WeekNumberHelper;

class CsrgtQuarterHelper extends AbstractWeekBasedQuarterHelper
{
    public function __construct()
    {
        $this->weekNumberHelper = new WeekNumberHelper();
    }
}
