<?php

namespace App\Utility\Quarter;

use App\Utility\International\WeekNumberHelper;

// N.B. This class is only used for reports
//     (International uses NaturalQuarterHelper for exports + cleanup)
class IrhsQuarterHelper extends AbstractWeekBasedQuarterHelper
{
    public function __construct()
    {
        $this->weekNumberHelper = new WeekNumberHelper();
    }
}
